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
        $projects = Project::withCount(['tasks as my_tasks_count' => fn ($query) => $query->where('assignee_id', auth()->id())])
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

        return view('employee.projects.index', ['projects' => $projects]);
    }

    public function show(Project $project): View
    {
        $this->abortUnlessAssignedProject($project);

        return view('employee.projects.show', [
            'project' => $project->load(['company', 'users', 'files.uploader']),
            'tasks' => $project->tasks()->where('assignee_id', auth()->id())->latest()->get(),
            'hours' => round($project->workSessions()->where('user_id', auth()->id())->sum('duration_minutes') / 60, 2),
        ]);
    }
}
