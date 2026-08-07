<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Employee\Concerns\HandlesEmployeeAccess;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    use HandlesEmployeeAccess;

    public function index(Request $request): View
    {
        abort_unless(! $request->filled('status') || in_array($request->status, ['planning', 'pending', 'approved', 'active', 'in_progress', 'on_hold', 'testing', 'completed', 'cancelled'], true), 404);
        abort_unless(! $request->filled('priority') || in_array($request->priority, ['low', 'medium', 'high', 'urgent'], true), 404);

        $baseQuery = Project::where('company_id', $this->companyId())
            ->where(fn ($query) => $query
                ->whereHas('users', fn ($users) => $users->where('users.id', auth()->id()))
                ->orWhereHas('tasks', fn ($tasks) => $tasks->where('assignee_id', auth()->id())));

        $projects = Project::withCount(['tasks as my_tasks_count' => fn ($query) => $query->where('assignee_id', auth()->id())])
            ->withCount(['tasks as my_open_tasks_count' => fn ($query) => $query->where('assignee_id', auth()->id())->whereIn('status', ['todo', 'assigned', 'in_progress', 'paused', 'blocked', 'submitted', 'under_review'])])
            ->with('company:id,name')
            ->where('company_id', $this->companyId())
            ->where(fn ($query) => $query
                ->whereHas('users', fn ($users) => $users->where('users.id', auth()->id()))
                ->orWhereHas('tasks', fn ($tasks) => $tasks->where('assignee_id', auth()->id())))
            ->when($request->search, fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->when($request->priority, fn ($query, $priority) => $query->where('priority', $priority))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('employee.projects.index', [
            'projects' => $projects,
            'summary' => [
                'assigned' => (clone $baseQuery)->count(),
                'active' => (clone $baseQuery)->whereIn('status', ['active', 'in_progress', 'testing'])->count(),
                'overdue' => (clone $baseQuery)->whereDate('due_date', '<', today())->whereNotIn('status', ['completed', 'cancelled'])->count(),
                'completed' => (clone $baseQuery)->where('status', 'completed')->count(),
            ],
        ]);
    }

    public function show(Project $project): View
    {
        $this->abortUnlessAssignedProject($project);

        $taskIds = $project->tasks()->where('assignee_id', auth()->id())->pluck('id');

        return view('employee.projects.show', [
            'project' => $project->load(['company', 'users', 'files' => fn ($query) => $query
                ->where(fn ($files) => $files->where('uploaded_by', auth()->id())->orWhereIn('task_id', $taskIds))
                ->latest(), 'files.uploader']),
            'tasks' => $project->tasks()->whereIn('id', $taskIds)->latest()->get(),
            'taskSummary' => [
                'total' => $taskIds->count(),
                'open' => $project->tasks()->whereIn('id', $taskIds)->whereIn('status', ['todo', 'assigned', 'in_progress', 'paused', 'blocked', 'submitted', 'under_review'])->count(),
                'completed' => $project->tasks()->whereIn('id', $taskIds)->where('status', 'completed')->count(),
                'overdue' => $project->tasks()->whereIn('id', $taskIds)->whereDate('due_date', '<', today())->whereNotIn('status', ['completed', 'cancelled'])->count(),
            ],
            'hours' => round($project->workSessions()->where('user_id', auth()->id())->sum('duration_minutes') / 60, 2),
        ]);
    }
}
