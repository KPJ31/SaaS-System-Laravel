<?php

namespace App\Http\Controllers\CompanyAdmin;

use App\Http\Controllers\CompanyAdmin\Concerns\HandlesCompanyAccess;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkSession;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
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
            'runningTimers' => WorkSession::where('company_id', $this->companyId())->whereNull('ended_at')->count(),
            'todayMinutes' => WorkSession::where('company_id', $this->companyId())->whereDate('started_at', today())->sum('duration_minutes'),
            'monthMinutes' => WorkSession::where('company_id', $this->companyId())->whereBetween('started_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('duration_minutes'),
        ]);
    }

    public function export(Request $request)
    {
        $rows = $this->filtered($request)->latest('started_at')->get();
        $csv = "Employee,Project,Task,Started,Ended,Minutes,Notes\n";

        foreach ($rows as $session) {
            $csv .= collect([
                $session->user?->name,
                $session->project?->name,
                $session->task?->title,
                $session->started_at?->toDateTimeString(),
                $session->ended_at?->toDateTimeString(),
                $session->duration_minutes,
                str_replace(["\r", "\n", ','], ' ', (string) $session->notes),
            ])->map(fn ($value) => '"'.$value.'"')->implode(',')."\n";
        }

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="work-sessions.csv"',
        ]);
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
        return WorkSession::with(['user', 'project', 'task'])
            ->where('company_id', $this->companyId())
            ->when($request->status === 'running', fn ($query) => $query->whereNull('ended_at'))
            ->when($request->employee_id, fn ($query, $employeeId) => $query->where('user_id', $employeeId))
            ->when($request->project_id, fn ($query, $projectId) => $query->where('project_id', $projectId))
            ->when($request->date_from, fn ($query, $date) => $query->whereDate('started_at', '>=', $date))
            ->when($request->date_to, fn ($query, $date) => $query->whereDate('started_at', '<=', $date));
    }
}
