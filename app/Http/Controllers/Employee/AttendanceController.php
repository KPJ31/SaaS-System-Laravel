<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Employee\Concerns\HandlesEmployeeAccess;
use App\Models\Attendance;
use App\Services\AttendanceService;
use App\Services\AuditLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    use HandlesEmployeeAccess;

    public function index(Request $request, AttendanceService $attendanceService): View
    {
        abort_unless(! $request->filled('status') || in_array($request->status, AttendanceService::STATUSES, true), 404);
        $settings = $attendanceService->settingsForCompany($this->companyId(), auth()->user()->company);
        $attendances = $this->filtered($request)
            ->latest('attendance_date')
            ->paginate(12)
            ->withQueryString();

        $summaryQuery = Attendance::where('company_id', $this->companyId())->where('user_id', auth()->id());

        return view('employee.attendance.index', [
            'attendances' => $attendances,
            'settings' => $settings,
            'todayAttendance' => $attendanceService->todayAttendance(auth()->user(), $settings),
            'summary' => [
                'present' => (clone $summaryQuery)->where('status', 'present')->count(),
                'late' => (clone $summaryQuery)->where('is_late', true)->count(),
                'half_day' => (clone $summaryQuery)->where('status', 'half_day')->count(),
                'absent' => (clone $summaryQuery)->where('status', 'absent')->count(),
                'worked_minutes' => (clone $summaryQuery)->sum('net_work_minutes'),
            ],
        ]);
    }

    public function checkIn(AttendanceService $attendanceService, AuditLogger $logger): RedirectResponse
    {
        $attendance = $attendanceService->checkIn(auth()->user());

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

    public function checkOut(Request $request, AttendanceService $attendanceService, AuditLogger $logger): RedirectResponse
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:1000']]);
        $attendance = $attendanceService->checkOut(auth()->user(), $data['note'] ?? null);

        $logger->record('attendance_checked_out', 'Employee checked out for attendance.', auth()->user(), $attendance, $this->companyId(), ['summary' => $attendance->only(['gross_minutes', 'lunch_break_minutes', 'net_work_minutes', 'status'])], $request);

        return back()->with('success', 'Attendance check-out recorded.');
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
}
