<?php

namespace App\Http\Controllers\CompanyAdmin;

use App\Http\Controllers\CompanyAdmin\Concerns\HandlesCompanyAccess;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkSession;
use App\Services\AuditLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkSessionController extends Controller
{
    use HandlesCompanyAccess;

    public function index(Request $request): View
    {
        $sessions = $this->filtered($request)->latest('started_at')->paginate(10)->withQueryString();

        return view('company-admin.work-sessions.index', [
            'sessions' => $sessions,
            'employees' => User::where('company_id', $this->companyId())->where('role', 'employee')->orderBy('name')->get(),
            'projects' => Project::where('company_id', $this->companyId())->orderBy('name')->get(),
            'tasks' => Task::where('company_id', $this->companyId())->orderBy('title')->get(),
            'runningTimers' => WorkSession::where('company_id', $this->companyId())->whereNull('ended_at')->count(),
            'pendingManual' => WorkSession::where('company_id', $this->companyId())->where('is_manual', true)->where('approval_status', 'pending')->count(),
            'todayMinutes' => WorkSession::where('company_id', $this->companyId())->whereDate('started_at', today())->sum('duration_minutes'),
            'monthMinutes' => WorkSession::where('company_id', $this->companyId())->whereBetween('started_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('duration_minutes'),
        ]);
    }

    public function export(Request $request)
    {
        $rows = $this->filtered($request)->latest('started_at')->get();

        return response()->streamDownload(function () use ($request, $rows): void {
            $out = fopen('php://output', 'w');
            $this->writeCsv($out, 'Work Sessions Report', $this->workSessionHeaders(), $this->workSessionRows($rows), $request);
            fclose($out);
        }, 'work-sessions.csv', ['Content-Type' => 'text/csv']);
    }

    public function exportPdf(Request $request)
    {
        $rows = $this->filtered($request)->latest('started_at')->get();

        return Pdf::loadView('reports.table-pdf', [
            'title' => 'Work Sessions Report',
            'scope' => $this->company()->name,
            'generatedAt' => now(),
            'headers' => $this->workSessionHeaders(),
            'rows' => $this->workSessionRows($rows),
            'filters' => $this->activeFilters($request),
        ])->setPaper('a4', 'landscape')->download('work-sessions.pdf');
    }

    public function update(Request $request, WorkSession $workSession, AuditLogger $logger): RedirectResponse
    {
        $this->abortUnlessCompanyRecord($workSession);
        abort_if($workSession->ended_at === null, 422, 'Running sessions cannot be corrected.');

        $data = $request->validate([
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'adjustment_reason' => ['required', 'string', 'max:1000'],
        ]);

        $old = $workSession->only(['duration_minutes', 'notes']);
        $workSession->update([
            'duration_minutes' => $data['duration_minutes'],
            'notes' => $data['notes'] ?? $workSession->notes,
            'status' => 'adjusted',
            'approval_status' => 'approved',
            'adjustment_reason' => $data['adjustment_reason'],
        ]);
        $logger->record('work_session_adjusted', 'Work session corrected with reason.', auth()->user(), $workSession, $this->companyId(), ['old' => $old, 'new' => $data], $request);

        return back()->with('success', 'Work session corrected.');
    }

    private function filtered(Request $request)
    {
        $this->authorizeFilters($request);

        return WorkSession::with(['user', 'project', 'task'])
            ->where('company_id', $this->companyId())
            ->when($request->status === 'running', fn ($query) => $query->whereNull('ended_at'))
            ->when($request->status === 'manual_pending', fn ($query) => $query->where('is_manual', true)->where('approval_status', 'pending'))
            ->when(in_array($request->status, ['stopped', 'adjusted'], true), fn ($query) => $query->where('status', $request->status))
            ->when($request->employee_id, fn ($query, $employeeId) => $query->where('user_id', $employeeId))
            ->when($request->project_id, fn ($query, $projectId) => $query->where('project_id', $projectId))
            ->when($request->task_id, fn ($query, $taskId) => $query->where('task_id', $taskId))
            ->when($request->date_from, fn ($query, $date) => $query->whereDate('started_at', '>=', $date))
            ->when($request->date_to, fn ($query, $date) => $query->whereDate('started_at', '<=', $date));
    }

    private function workSessionHeaders(): array
    {
        return ['Employee', 'Project', 'Task', 'Started', 'Ended', 'Minutes', 'Notes', 'Status', 'Source', 'Approval'];
    }

    private function workSessionRows($rows)
    {
        return $rows->map(fn (WorkSession $session) => [
            $session->user?->name ?? '-',
            $session->project?->name ?? '-',
            $session->task?->title ?? '-',
            $session->started_at?->toDateTimeString() ?? '-',
            $session->ended_at?->toDateTimeString() ?? 'Running',
            $session->duration_minutes,
            $session->notes ?? '-',
            $session->status,
            $session->is_manual ? 'Manual' : 'Timer',
            $session->approval_status ?? '-',
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
        return collect($request->only(['employee_id', 'project_id', 'task_id', 'date_from', 'date_to', 'status']))
            ->filter(fn ($value) => filled($value))
            ->mapWithKeys(fn ($value, $key) => [str_replace('_', ' ', ucfirst($key)) => (string) $value])
            ->all();
    }

    private function authorizeFilters(Request $request): void
    {
        abort_unless(! $request->filled('status') || in_array($request->status, ['running', 'stopped', 'adjusted', 'manual_pending'], true), 404);

        if ($request->filled('employee_id')) {
            abort_unless(User::where('company_id', $this->companyId())->where('role', 'employee')->whereKey($request->integer('employee_id'))->exists(), 403);
        }

        if ($request->filled('project_id')) {
            abort_unless(Project::where('company_id', $this->companyId())->whereKey($request->integer('project_id'))->exists(), 403);
        }

        if ($request->filled('task_id')) {
            abort_unless(Task::where('company_id', $this->companyId())->whereKey($request->integer('task_id'))->exists(), 403);
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
