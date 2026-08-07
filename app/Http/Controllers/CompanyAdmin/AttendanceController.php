<?php

namespace App\Http\Controllers\CompanyAdmin;

use App\Http\Controllers\CompanyAdmin\Concerns\HandlesCompanyAccess;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use App\Models\WorkSession;
use App\Services\AttendanceService;
use App\Services\AuditLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    use HandlesCompanyAccess;

    public function index(Request $request, AttendanceService $attendanceService): View
    {
        abort_unless(! $request->filled('status') || in_array($request->status, AttendanceService::STATUSES, true), 404);
        $date = $request->date ?: today()->toDateString();
        $attendances = $this->filtered($request)
            ->latest('attendance_date')
            ->paginate(15)
            ->withQueryString();

        $stats = $attendanceService->companyDayStats($this->companyId(), $date);

        return view('company-admin.attendance.index', [
            'attendances' => $attendances,
            'employees' => User::where('company_id', $this->companyId())->where('role', 'employee')->orderBy('name')->get(),
            'date' => $date,
            'stats' => $stats + [
                'working' => WorkSession::where('company_id', $this->companyId())->where('status', 'running')->whereNull('ended_at')->count(),
            ],
        ]);
    }

    public function export(Request $request)
    {
        $rows = $this->filtered($request)->latest('attendance_date')->get();

        return response()->streamDownload(function () use ($request, $rows): void {
            $out = fopen('php://output', 'w');
            $this->writeCsv($out, 'Attendance Report', $this->attendanceHeaders(), $this->attendanceRows($rows), $request);
            fclose($out);
        }, 'attendance-report.csv', ['Content-Type' => 'text/csv']);
    }

    public function exportPdf(Request $request)
    {
        $rows = $this->filtered($request)->latest('attendance_date')->get();

        return Pdf::loadView('reports.table-pdf', [
            'title' => 'Attendance Report',
            'scope' => $this->company()->name,
            'generatedAt' => now(),
            'headers' => $this->attendanceHeaders(),
            'rows' => $this->attendanceRows($rows),
            'filters' => $this->activeFilters($request),
        ])->setPaper('a4', 'landscape')->download('attendance-report.pdf');
    }

    public function update(Request $request, Attendance $attendance, AttendanceService $attendanceService, AuditLogger $logger): RedirectResponse
    {
        $this->abortUnlessCompanyRecord($attendance);
        $data = $request->validate([
            'check_in_time' => ['required', 'date', 'before_or_equal:now'],
            'check_out_time' => ['required', 'date', 'after:check_in_time', 'before_or_equal:now'],
            'note' => ['nullable', 'string', 'max:1000'],
            'correction_reason' => ['required', 'string', 'max:1000'],
        ]);

        $settings = $attendanceService->settingsForCompany($this->companyId(), $this->company());
        $checkIn = Carbon::parse($data['check_in_time'], $settings['timezone']);
        $checkOut = Carbon::parse($data['check_out_time'], $settings['timezone']);
        $old = $attendance->only(['check_in_time', 'check_out_time', 'gross_minutes', 'net_work_minutes', 'status']);

        $attendance->update($attendanceService->calculateSummary($checkIn, $checkOut, $settings) + [
            'attendance_date' => $checkIn->toDateString(),
            'check_in_time' => $checkIn,
            'check_out_time' => $checkOut,
            'note' => $data['note'] ?? $attendance->note,
            'corrected_by' => auth()->id(),
            'correction_reason' => $data['correction_reason'],
            'correction_snapshot' => ['old' => $old],
        ]);

        $logger->record('attendance_corrected', 'Attendance corrected with reason.', auth()->user(), $attendance, $this->companyId(), ['old' => $old, 'new' => $attendance->fresh()->toArray()], $request);

        return back()->with('success', 'Attendance corrected.');
    }

    private function filtered(Request $request)
    {
        $this->authorizeFilters($request);

        return Attendance::with('user')
            ->where('company_id', $this->companyId())
            ->when($request->employee_id, fn ($query, $employeeId) => $query->where('user_id', $employeeId))
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->when($request->date, fn ($query, $date) => $query->whereDate('attendance_date', $date))
            ->when($request->date_from, fn ($query, $date) => $query->whereDate('attendance_date', '>=', $date))
            ->when($request->date_to, fn ($query, $date) => $query->whereDate('attendance_date', '<=', $date))
            ->when($request->search, fn ($query, $search) => $query->whereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")));
    }

    private function attendanceHeaders(): array
    {
        return ['Employee', 'Date', 'Check In', 'Check Out', 'Gross Minutes', 'Lunch Minutes', 'Net Minutes', 'Status', 'Late Minutes', 'Early Departure', 'Note'];
    }

    private function attendanceRows($rows)
    {
        return $rows->map(fn (Attendance $attendance) => [
            $attendance->user?->name ?? '-',
            $attendance->attendance_date?->toDateString() ?? '-',
            $attendance->check_in_time?->toDateTimeString() ?? '-',
            $attendance->check_out_time?->toDateTimeString() ?? '-',
            $attendance->gross_minutes,
            $attendance->lunch_break_minutes,
            $attendance->net_work_minutes,
            str_replace('_', ' ', ucfirst($attendance->status)),
            $attendance->late_minutes,
            $attendance->early_departure_minutes,
            $attendance->note ?? '-',
        ]);
    }

    private function writeCsv($out, string $title, array $headers, $rows, Request $request): void
    {
        fputcsv($out, $this->safeCsvRow([$title]));
        fputcsv($out, $this->safeCsvRow(['Company', $this->company()->name]));
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
        return collect($request->only(['date', 'employee_id', 'status', 'search', 'date_from', 'date_to']))
            ->filter(fn ($value) => filled($value))
            ->mapWithKeys(fn ($value, $key) => [str_replace('_', ' ', ucfirst($key)) => (string) $value])
            ->all();
    }

    private function authorizeFilters(Request $request): void
    {
        if ($request->filled('employee_id')) {
            abort_unless(User::where('company_id', $this->companyId())->where('role', 'employee')->whereKey($request->integer('employee_id'))->exists(), 403);
        }
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
