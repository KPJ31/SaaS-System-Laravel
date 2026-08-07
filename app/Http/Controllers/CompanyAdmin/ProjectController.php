<?php

namespace App\Http\Controllers\CompanyAdmin;

use App\Http\Controllers\CompanyAdmin\Concerns\HandlesCompanyAccess;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
use App\Models\ProjectRequest;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkFile;
use App\Models\WorkSession;
use App\Services\AuditLogger;
use App\Services\ProjectProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProjectController extends Controller
{
    use HandlesCompanyAccess;

    public function index(Request $request): View
    {
        $this->authorizeFilters($request);

        $baseQuery = Project::where('company_id', $this->companyId());

        $projects = Project::with(['client', 'manager'])
            ->withCount([
                'tasks',
                'users as team_count',
                'tasks as open_tasks_count' => fn ($query) => $query->whereIn('status', ['todo', 'assigned', 'in_progress', 'paused', 'blocked', 'submitted', 'under_review']),
                'tasks as overdue_tasks_count' => fn ($query) => $query->whereDate('due_date', '<', today())->whereNotIn('status', ['completed', 'cancelled']),
            ])
            ->where('company_id', $this->companyId())
            ->when($request->search, fn ($query, $search) => $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhereHas('client', fn ($client) => $client->where('name', 'like', "%{$search}%")->orWhere('company_name', 'like', "%{$search}%"))
                ->orWhereHas('manager', fn ($manager) => $manager->where('name', 'like', "%{$search}%"))))
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->when($request->priority, fn ($query, $priority) => $query->where('priority', $priority))
            ->when($request->client_id, fn ($query, $clientId) => $query->where('client_id', $clientId))
            ->when($request->manager_id, fn ($query, $managerId) => $query->where('manager_id', $managerId))
            ->when($request->deadline === 'overdue', fn ($query) => $query->whereDate('due_date', '<', today())->whereNotIn('status', ['completed', 'cancelled']))
            ->when($request->deadline === 'upcoming', fn ($query) => $query->whereDate('due_date', '>=', today())->whereDate('due_date', '<=', today()->addDays(14))->whereNotIn('status', ['completed', 'cancelled']))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('company-admin.projects.index', [
            'projects' => $projects,
            'clients' => Client::where('company_id', $this->companyId())->orderBy('name')->get(),
            'managers' => $this->managerOptions(),
            'summary' => [
                'active' => (clone $baseQuery)->whereIn('status', ['active', 'in_progress', 'testing'])->count(),
                'planning' => (clone $baseQuery)->whereIn('status', ['planning', 'pending', 'approved'])->count(),
                'overdue' => (clone $baseQuery)->whereDate('due_date', '<', today())->whereNotIn('status', ['completed', 'cancelled'])->count(),
                'completed' => (clone $baseQuery)->where('status', 'completed')->count(),
            ],
        ]);
    }

    public function create(): View|RedirectResponse
    {
        if ($this->subscriptionLimitReached('project_limit', Project::class)) {
            return redirect()->route('company-admin.projects.index')->with('error', $this->limitMessage());
        }

        return $this->form(new Project());
    }

    public function store(Request $request, AuditLogger $logger): RedirectResponse
    {
        if ($this->subscriptionLimitReached('project_limit', Project::class)) {
            return back()->withInput()->with('error', $this->limitMessage());
        }

        $data = $this->validated($request);
        $teamMemberIds = $data['team_member_ids'] ?? [];
        unset($data['team_member_ids']);
        $data['company_id'] = $this->companyId();
        $this->validateRelated($data);

        $project = DB::transaction(function () use ($data, $teamMemberIds, $logger): Project {
            $project = Project::create($data);
            $project->users()->sync($this->validTeamMemberIds($teamMemberIds));
            $logger->record('project_created', 'Project created.', auth()->user(), $project, $this->companyId(), request: request());

            return $project;
        });

        return redirect()->route('company-admin.projects.show', $project)->with('success', 'Project created successfully.');
    }

    public function show(Project $project): View
    {
        $this->abortUnlessCompanyRecord($project);

        return view('company-admin.projects.show', [
            'project' => $project->load(['client', 'manager', 'projectRequest', 'users', 'tasks.assignee', 'payments', 'invoices']),
            'employees' => $this->activeEmployees(),
            'progressValue' => app(ProjectProgressService::class)->calculate($project),
            'tasks' => Task::with('assignee:id,name')
                ->where('company_id', $this->companyId())
                ->where('project_id', $project->id)
                ->latest()
                ->take(10)
                ->get(),
            'taskSummary' => [
                'total' => $project->tasks()->count(),
                'open' => $project->tasks()->whereIn('status', ['todo', 'assigned', 'in_progress', 'paused', 'blocked', 'submitted', 'under_review'])->count(),
                'completed' => $project->tasks()->where('status', 'completed')->count(),
                'overdue' => $project->tasks()->whereDate('due_date', '<', today())->whereNotIn('status', ['completed', 'cancelled'])->count(),
            ],
            'documents' => WorkFile::with('uploader:id,name')
                ->where('company_id', $this->companyId())
                ->where('project_id', $project->id)
                ->latest()
                ->take(8)
                ->get(),
            'workSessions' => WorkSession::with(['user:id,name', 'task:id,title'])
                ->where('company_id', $this->companyId())
                ->where('project_id', $project->id)
                ->latest('started_at')
                ->take(8)
                ->get(),
            'timeSummary' => [
                'hours' => round($project->workSessions()->sum('duration_minutes') / 60, 1),
                'sessions' => $project->workSessions()->count(),
                'running' => $project->workSessions()->whereNull('ended_at')->count(),
            ],
            'financeSummary' => [
                'invoice_total' => (float) Invoice::where('company_id', $this->companyId())->where('project_id', $project->id)->sum('total'),
                'invoice_balance' => (float) Invoice::where('company_id', $this->companyId())->where('project_id', $project->id)->sum('balance_amount'),
                'paid_payments' => (float) Payment::where('company_id', $this->companyId())->where('project_id', $project->id)->whereIn('status', ['paid', 'received', 'verified'])->sum('amount'),
                'pending_payments' => Payment::where('company_id', $this->companyId())->where('project_id', $project->id)->whereIn('status', ['pending', 'requested', 'proof_submitted'])->count(),
            ],
            'activityLogs' => AuditLog::with('user')
                ->where('company_id', $this->companyId())
                ->where(fn ($query) => $query
                    ->where(fn ($q) => $q->where('auditable_type', Project::class)->where('auditable_id', $project->id))
                    ->orWhere(fn ($q) => $q->where('auditable_type', Task::class)->whereIn('auditable_id', $project->tasks()->pluck('id'))))
                ->latest()
                ->take(10)
                ->get(),
        ]);
    }

    public function edit(Project $project): View
    {
        $this->abortUnlessCompanyRecord($project);

        return $this->form($project);
    }

    public function update(Request $request, Project $project, AuditLogger $logger): RedirectResponse
    {
        $this->abortUnlessCompanyRecord($project);
        $data = $this->validated($request);
        $teamMemberIds = $data['team_member_ids'] ?? [];
        unset($data['team_member_ids']);
        $this->validateRelated($data);

        DB::transaction(function () use ($project, $data, $teamMemberIds, $logger): void {
            $project->update($data);
            $project->users()->sync($this->validTeamMemberIds($teamMemberIds));
            $logger->record('project_updated', 'Project updated.', auth()->user(), $project, $this->companyId(), request: request());
        });

        return redirect()->route('company-admin.projects.show', $project)->with('success', 'Project updated successfully.');
    }

    public function destroy(Project $project, AuditLogger $logger): RedirectResponse
    {
        $this->abortUnlessCompanyRecord($project);
        $project->update(['status' => 'cancelled']);
        $logger->record('project_cancelled', 'Project cancelled.', auth()->user(), $project, $this->companyId(), request: request());

        return redirect()->route('company-admin.projects.index')->with('success', 'Project cancelled.');
    }

    public function assignEmployee(Request $request, Project $project, AuditLogger $logger): RedirectResponse
    {
        $this->abortUnlessCompanyRecord($project);

        $data = $request->validate(['user_id' => ['required', 'integer', Rule::exists('users', 'id')->where(fn ($query) => $query->where('company_id', $this->companyId())->where('role', 'employee')->where('status', 'active'))]]);
        $employee = User::where('company_id', $this->companyId())->where('role', 'employee')->where('status', 'active')->findOrFail($data['user_id']);
        $project->users()->syncWithoutDetaching([$employee->id]);
        $logger->record('project_employee_assigned', 'Employee assigned to project.', auth()->user(), $project, $this->companyId(), ['employee_id' => $employee->id], $request);

        return back()->with('success', 'Employee assigned to project.');
    }

    public function removeEmployee(Project $project, User $employee, AuditLogger $logger): RedirectResponse
    {
        $this->abortUnlessCompanyRecord($project);
        abort_unless((int) $employee->company_id === $this->companyId() && $employee->role === 'employee', 403);
        $project->users()->detach($employee->id);
        $logger->record('project_employee_removed', 'Employee removed from project.', auth()->user(), $project, $this->companyId(), ['employee_id' => $employee->id], request());

        return back()->with('success', 'Employee removed from project.');
    }

    private function form(Project $project): View
    {
        return view('company-admin.projects.form', [
            'project' => $project,
            'clients' => Client::where('company_id', $this->companyId())->orderBy('name')->get(),
            'employees' => User::where('company_id', $this->companyId())->where('role', 'employee')->orderBy('name')->get(),
            'managers' => $this->managerOptions(),
            'requests' => ProjectRequest::where('company_id', $this->companyId())->whereIn('status', ['approved', 'payment_confirmed'])->orderBy('title')->get(),
        ]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'manager_id' => ['nullable', 'integer', 'exists:users,id'],
            'project_request_id' => ['nullable', 'integer', 'exists:project_requests,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:4000'],
            'status' => ['required', 'in:planning,pending,approved,active,in_progress,on_hold,testing,completed,cancelled'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'promised_end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'completed_date' => ['nullable', 'date'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:4000'],
            'team_member_ids' => ['nullable', 'array'],
            'team_member_ids.*' => ['integer', Rule::exists('users', 'id')->where(fn ($query) => $query->where('company_id', $this->companyId())->where('role', 'employee')->where('status', 'active'))],
        ]);
    }

    private function validateRelated(array $data): void
    {
        foreach (['client_id' => Client::class, 'project_request_id' => ProjectRequest::class] as $key => $model) {
            if (! empty($data[$key]) && ! $model::where('company_id', $this->companyId())->whereKey($data[$key])->exists()) {
                abort(403);
            }
        }

        if (! empty($data['manager_id']) && ! User::where('company_id', $this->companyId())->whereKey($data['manager_id'])->exists()) {
            abort(403);
        }
    }

    private function authorizeFilters(Request $request): void
    {
        if ($request->filled('client_id')) {
            abort_unless(Client::where('company_id', $this->companyId())->whereKey($request->integer('client_id'))->exists(), 403);
        }

        if ($request->filled('manager_id')) {
            abort_unless(User::where('company_id', $this->companyId())->whereKey($request->integer('manager_id'))->exists(), 403);
        }
    }

    private function validTeamMemberIds(array $ids): array
    {
        return User::where('company_id', $this->companyId())
            ->where('role', 'employee')
            ->where('status', 'active')
            ->whereIn('id', array_unique(array_map('intval', $ids)))
            ->pluck('id')
            ->all();
    }

    private function activeEmployees()
    {
        return User::where('company_id', $this->companyId())->where('role', 'employee')->where('status', 'active')->orderBy('name')->get();
    }

    private function managerOptions()
    {
        return User::where('company_id', $this->companyId())
            ->whereIn('role', ['company_admin', 'employee'])
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }
}
