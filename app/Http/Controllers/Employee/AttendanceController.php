<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Employee\Concerns\HandlesEmployeeAccess;
use App\Models\Attendance;
use App\Models\CompanySetting;
use App\Models\LeaveRequest;
use App\Services\AuditLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    use HandlesEmployeeAccess;

    public function index(Request $request): View
    {
        $settings = $this->attendanceSettings();
        $attendances = $this->filtered($request)
            ->latest('attendance_date')
            ->paginate(12)
            ->withQueryString();

        $summaryQuery = Attendance::where('company_id', $this->companyId())->where('user_id', auth()->id());

        return view('employee.attendance.index', [
            'attendances' => $attendances,
            'settings' => $settings,
            'summary' => [
                'present' => (clone $summaryQuery)->where('status', 'present')->count(),
                'late' => (clone $summaryQuery)->where('is_late', true)->count(),
                'half_day' => (clone $summaryQuery)->where('status', 'half_day')->count(),
                'absent' => (clone $summaryQuery)->where('status', 'absent')->count(),
                'worked_minutes' => (clone $summaryQuery)->sum('net_work_minutes'),
            ],
        ]);
    }

    public function checkIn(AuditLogger $logger): RedirectResponse
    {
        $settings = $this->attendanceSettings();
        $now = now($settings['timezone']);
        $date = $now->toDateString();

        $this->ensureAttendanceAllowed($settings, $now);

        if ($this->approvedLeaveExists($date)) {
            throw ValidationException::withMessages(['attendance' => 'You have approved leave for today. Attendance check-in is not required.']);
        }

        if (Attendance::where('company_id', $this->companyId())->where('user_id', auth()->id())->whereDate('attendance_date', $date)->exists()) {
            throw ValidationException::withMessages(['attendance' => 'You have already checked in for today.']);
        }

        $start = Carbon::parse($date.' '.$settings['work_start_time'], $settings['timezone']);
        $allowedArrival = $start->copy()->addMinutes((int) $settings['late_grace_minutes']);
        $isLate = $now->gt($allowedArrival);

        $attendance = Attendance::create([
            'company_id' => $this->companyId(),
            'user_id' => auth()->id(),
            'attendance_date' => $date,
            'check_in_time' => $now,
            'status' => $isLate ? 'late' : 'present',
            'is_late' => $isLate,
            'late_minutes' => $isLate ? $start->diffInMinutes($now) : 0,
        ]);

        $logger->record('attendance_checked_in', 'Employee checked in for attendance.', auth()->user(), $attendance, $this->companyId(), request: request());

        return back()->with('success', 'Attendance check-in recorded.');
    }

    public function export(Request $request): StreamedResponse
    {
        $attendances = $this->filtered($request)->latest('attendance_date')->get();

        return response()->streamDownload(function () use ($request, $attendances): void {
            $out = fopen('php://output', 'w');
            $this->writeCsv($out, 'My Attendance Report', $this->headers(), $this->rows($attendances), $request);
            fclose($out);
        }, 'my-attendance.csv', ['Content-Type' => 'text/csv']);
    }

    public function exportPdf(Request $request)
    {
        $attendances = $this->filtered($request)->latest('attendance_date')->get();

        return Pdf::loadView('reports.table-pdf', [
            'title' => 'My Attendance Report',
            'scope' => auth()->user()->name,
            'generatedAt' => now(),
            'headers' => $this->headers(),
            'rows' => $this->rows($attendances),
            'filters' => $this->activeFilters($request),
        ])->setPaper('a4', 'landscape')->download('my-attendance.pdf');
    }

    public function checkOut(Request $request, AuditLogger $logger): RedirectResponse
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:1000']]);
        $settings = $this->attendanceSettings();
        $now = now($settings['timezone']);
        $date = $now->toDateString();

        $attendance = Attendance::where('company_id', $this->companyId())
            ->where('user_id', auth()->id())
            ->whereDate('attendance_date', $date)
            ->first();

        if (! $attendance || ! $attendance->check_in_time) {
            throw ValidationException::withMessages(['attendance' => 'Please check in before checking out.']);
        }

        if ($attendance->check_out_time) {
            throw ValidationException::withMessages(['attendance' => 'You have already checked out for today.']);
        }

        if ($now->lte($attendance->check_in_time->timezone($settings['timezone']))) {
            throw ValidationException::withMessages(['attendance' => 'Check-out time must be after check-in time.']);
        }

        $summary = $this->calculateSummary($attendance->check_in_time->timezone($settings['timezone']), $now, $settings);
        $attendance->update($summary + [
            'check_out_time' => $now,
            'note' => $data['note'] ?? $attendance->note,
        ]);

        $logger->record('attendance_checked_out', 'Employee checked out for attendance.', auth()->user(), $attendance, $this->companyId(), ['summary' => $summary], $request);

        return back()->with('success', 'Attendance check-out recorded.');
    }

    private function attendanceSettings(): array
    {
        $setting = CompanySetting::firstOrCreate(
            ['company_id' => $this->companyId()],
            ['timezone' => auth()->user()->company?->timezone ?? 'UTC', 'currency' => 'USD', 'settings' => []]
        );

        return $setting->attendanceSettings() + ['timezone' => $setting->timezone ?: 'UTC'];
    }

    private function filtered(Request $request)
    {
        return Attendance::where('company_id', $this->companyId())
            ->where('user_id', auth()->id())
            ->when($request->date_from, fn ($query, $date) => $query->whereDate('attendance_date', '>=', $date))
            ->when($request->date_to, fn ($query, $date) => $query->whereDate('attendance_date', '<=', $date))
            ->when($request->status, fn ($query, $status) => $query->where('status', $status));
    }

    private function headers(): array
    {
        return ['Date', 'Day', 'Check In', 'Check Out', 'Gross', 'Lunch', 'Net', 'Status', 'Late', 'Early', 'Note'];
    }

    private function rows($attendances)
    {
        return $attendances->map(fn (Attendance $attendance) => [
            $attendance->attendance_date?->format('Y-m-d') ?? '-',
            $attendance->attendance_date?->format('l') ?? '-',
            $attendance->check_in_time?->format('H:i') ?? '-',
            $attendance->check_out_time?->format('H:i') ?? '-',
            $attendance->gross_minutes,
            $attendance->lunch_break_minutes,
            $attendance->net_work_minutes,
            str_replace('_', ' ', ucfirst($attendance->status)),
            $attendance->late_minutes ?: '-',
            $attendance->early_departure_minutes ?: '-',
            $attendance->note ?? '-',
        ]);
    }

    private function writeCsv($out, string $title, array $headers, $rows, Request $request): void
    {
        fputcsv($out, $this->safeCsvRow([$title]));
        fputcsv($out, $this->safeCsvRow(['Employee', auth()->user()->name]));
        fputcsv($out, $this->safeCsvRow(['Generated At', now()->format('Y-m-d H:i')]));
        foreach ($this->activeFilters($request) as $label => $value) {
            fputcsv($out, $this->safeCsvRow([$label, $value]));
        }
        fputcsv($out, []);
        fputcsv($out, $this->safeCsvRow($headers));
        foreach ($rows as $row) {
            fputcsv($out, $this->safeCsvRow($row));
        }
    }

    private function activeFilters(Request $request): array
    {
        return collect($request->only(['date_from', 'date_to', 'status']))
            ->filter(fn ($value) => filled($value))
            ->mapWithKeys(fn ($value, $key) => [str_replace('_', ' ', ucfirst($key)) => (string) $value])
            ->all();
    }

    private function safeCsvRow(iterable $row): array
    {
        return collect($row)
            ->map(function ($value): string {
                $value = (string) $value;

                return preg_match('/^[=+\-@]/', $value) ? "'".$value : $value;
            })
            ->all();
    }

    private function ensureAttendanceAllowed(array $settings, Carbon $now): void
    {
        if (! $settings['attendance_enabled']) {
            throw ValidationException::withMessages(['attendance' => 'Attendance tracking is disabled for your company.']);
        }

        if (! in_array($now->dayOfWeekIso, array_map('intval', $settings['working_days']), true)) {
            throw ValidationException::withMessages(['attendance' => 'Today is not configured as a working day.']);
        }
    }

    private function approvedLeaveExists(string $date): bool
    {
        return LeaveRequest::where('company_id', $this->companyId())
            ->where('user_id', auth()->id())
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->exists();
    }

    private function calculateSummary(Carbon $checkIn, Carbon $checkOut, array $settings): array
    {
        $gross = max(1, $checkIn->diffInMinutes($checkOut));
        $lunch = $gross >= 300 ? (int) $settings['lunch_break_minutes'] : 0;
        $net = max(0, $gross - $lunch);
        $end = Carbon::parse($checkOut->toDateString().' '.$settings['work_end_time'], $settings['timezone']);
        $earlyLimit = $end->copy()->subMinutes((int) $settings['early_departure_grace_minutes']);
        $isEarly = $checkOut->lt($earlyLimit);

        $status = $net >= (int) $settings['full_day_minutes'] ? 'present' : 'half_day';

        return [
            'gross_minutes' => $gross,
            'lunch_break_minutes' => $lunch,
            'net_work_minutes' => $net,
            'status' => $status,
            'is_early_departure' => $isEarly,
            'early_departure_minutes' => $isEarly ? $checkOut->diffInMinutes($end) : 0,
        ];
    }
}
