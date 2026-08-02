<?php

namespace App\Http\Controllers\CompanyAdmin;

use App\Http\Controllers\CompanyAdmin\Concerns\HandlesCompanyAccess;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    use HandlesCompanyAccess;

    public function index(Request $request): View
    {
        $projects = Project::with('client')->withCount('tasks')
            ->where('company_id', $this->companyId())
            ->when($request->search, fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->when($request->client_id, fn ($query, $clientId) => $query->where('client_id', $clientId))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('company-admin.projects.index', [
            'projects' => $projects,
            'clients' => Client::where('company_id', $this->companyId())->orderBy('name')->get(),
        ]);
    }

    public function create(): View|RedirectResponse
    {
        if ($this->subscriptionLimitReached('project_limit', Project::class)) {
            return redirect()->route('company-admin.projects.index')->with('error', $this->limitMessage());
        }

        return $this->form(new Project());
    }

    public function store(Request $request): RedirectResponse
    {
        if ($this->subscriptionLimitReached('project_limit', Project::class)) {
            return back()->withInput()->with('error', $this->limitMessage());
        }

        $data = $this->validated($request);
        $data['company_id'] = $this->companyId();
        $this->validateRelated($data);

        $project = Project::create($data);

        return redirect()->route('company-admin.projects.show', $project)->with('success', 'Project created successfully.');
    }

    public function show(Project $project): View
    {
        $this->abortUnlessCompanyRecord($project);

        return view('company-admin.projects.show', [
            'project' => $project->load(['client', 'users', 'tasks.assignee', 'payments', 'invoices']),
            'employees' => $this->activeEmployees(),
        ]);
    }

    public function edit(Project $project): View
    {
        $this->abortUnlessCompanyRecord($project);

        return $this->form($project);
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $this->abortUnlessCompanyRecord($project);
        $data = $this->validated($request);
        $this->validateRelated($data);
        $project->update($data);

        return redirect()->route('company-admin.projects.show', $project)->with('success', 'Project updated successfully.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $this->abortUnlessCompanyRecord($project);
        $project->update(['status' => 'cancelled']);

        return redirect()->route('company-admin.projects.index')->with('success', 'Project cancelled.');
    }

    public function assignEmployee(Request $request, Project $project): RedirectResponse
    {
        $this->abortUnlessCompanyRecord($project);

        $data = $request->validate(['user_id' => ['required', 'integer', 'exists:users,id']]);
        $employee = User::where('company_id', $this->companyId())->where('role', 'employee')->where('status', 'active')->findOrFail($data['user_id']);
        $project->users()->syncWithoutDetaching([$employee->id]);

        return back()->with('success', 'Employee assigned to project.');
    }

    public function removeEmployee(Project $project, User $employee): RedirectResponse
    {
        $this->abortUnlessCompanyRecord($project);
        abort_unless((int) $employee->company_id === $this->companyId(), 403);
        $project->users()->detach($employee->id);

        return back()->with('success', 'Employee removed from project.');
    }

    private function form(Project $project): View
    {
        return view('company-admin.projects.form', [
            'project' => $project,
            'clients' => Client::where('company_id', $this->companyId())->orderBy('name')->get(),
            'employees' => User::where('company_id', $this->companyId())->where('role', 'employee')->orderBy('name')->get(),
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
            'due_date' => ['nullable', 'date'],
            'promised_end_date' => ['nullable', 'date'],
            'completed_date' => ['nullable', 'date'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:4000'],
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

    private function activeEmployees()
    {
        return User::where('company_id', $this->companyId())->where('role', 'employee')->where('status', 'active')->orderBy('name')->get();
    }
}
