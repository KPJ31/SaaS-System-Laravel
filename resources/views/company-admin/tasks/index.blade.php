@extends('layouts.app')

@section('title', 'Tasks - Elevanix')

@section('content')
@include('partials.page-header', [
    'breadcrumbs' => [
        ['label' => 'Dashboard', 'url' => route('company-admin.dashboard')],
        ['label' => 'Tasks'],
    ],
    'eyebrow' => 'Company Admin',
    'title' => 'Tasks',
    'description' => 'Manage company tasks, assignments, deadlines and review activity.',
    'actions' => new \Illuminate\Support\HtmlString('<a class="btn btn-outline-primary" href="'.route('company-admin.tasks.kanban').'"><i class="fa-solid fa-table-columns"></i>Kanban</a><a class="btn btn-primary" href="'.route('company-admin.tasks.create').'"><i class="fa-solid fa-plus"></i>New Task</a>'),
])

<div class="stat-grid mb-3">
    @include('partials.stat-card', ['label' => 'Todo', 'value' => $summary['todo'], 'icon' => 'fa-clipboard-list', 'tone' => 'blue'])
    @include('partials.stat-card', ['label' => 'In Progress', 'value' => $summary['in_progress'], 'icon' => 'fa-spinner', 'tone' => 'green'])
    @include('partials.stat-card', ['label' => 'Pending Review', 'value' => $summary['review'], 'icon' => 'fa-paper-plane', 'tone' => 'yellow'])
    @include('partials.stat-card', ['label' => 'Overdue', 'value' => $summary['overdue'], 'icon' => 'fa-triangle-exclamation', 'tone' => 'danger'])
</div>

<section class="content-card mb-3">
    <form class="row g-2" method="GET">
        <div class="col-lg-3 col-md-6"><label class="form-label" for="search">Search</label><input class="form-control" id="search" name="search" value="{{ request('search') }}" placeholder="Task, project or assignee"></div>
        <div class="col-lg-2 col-md-6"><label class="form-label" for="project_id">Project</label><select class="form-select" id="project_id" name="project_id"><option value="">All projects</option>@foreach($projects as $project)<option value="{{ $project->id }}" @selected((int) request('project_id') === $project->id)>{{ $project->name }}</option>@endforeach</select></div>
        <div class="col-lg-2 col-md-6"><label class="form-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">All statuses</option>@foreach($statuses as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ $workflow->label($status) }}</option>@endforeach</select></div>
        <div class="col-lg-2 col-md-6"><label class="form-label" for="priority">Priority</label><select class="form-select" id="priority" name="priority"><option value="">All priorities</option>@foreach($priorities as $priority)<option value="{{ $priority }}" @selected(request('priority') === $priority)>{{ ucfirst($priority) }}</option>@endforeach</select></div>
        <div class="col-lg-2 col-md-6"><label class="form-label" for="assignee_id">Assignee</label><select class="form-select" id="assignee_id" name="assignee_id"><option value="">All employees</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected((int) request('assignee_id') === $employee->id)>{{ $employee->name }}</option>@endforeach</select></div>
        <div class="col-lg-1 col-md-6"><label class="form-label" for="due">Due</label><select class="form-select" id="due" name="due"><option value="">Any</option><option value="today" @selected(request('due') === 'today')>Today</option><option value="overdue" @selected(request('due') === 'overdue')>Late</option><option value="upcoming" @selected(request('due') === 'upcoming')>Soon</option></select></div>
        <div class="col-12 d-flex flex-wrap gap-2"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter"></i>Filter</button><a class="btn btn-outline-primary" href="{{ route('company-admin.tasks.index') }}">Clear</a></div>
    </form>
</section>

<section class="content-card">
    <div class="content-card-header"><div><h2>{{ $tasks->total() }} Tasks</h2><p>Assignments, review state and deadline pressure.</p></div></div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Task</th><th>Project</th><th>Assignee</th><th>Priority</th><th>Due Date</th><th>Status</th><th>Progress</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                @forelse($tasks as $task)
                    <tr>
                        <td><strong>{{ $task->title }}</strong><small>{{ ucfirst($task->task_type ?? 'task') }}</small></td>
                        <td>{{ $task->project?->name ?? '-' }}</td>
                        <td>{{ $task->assignee?->name ?? 'Unassigned' }}</td>
                        <td>@include('partials.priority-badge', ['priority' => $task->priority])</td>
                        <td>{{ $task->due_date?->format('M d, Y') ?? '-' }} @if($task->isOverdue()) @include('partials.status-badge', ['status' => 'overdue']) @endif</td>
                        <td>@include('partials.status-badge', ['status' => $task->status])</td>
                        <td style="min-width: 120px;"><div class="progress" role="progressbar" aria-valuenow="{{ (int) $task->progress }}" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar" style="width: {{ (int) $task->progress }}%"></div></div><small>{{ (int) $task->progress }}%</small></td>
                        <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('company-admin.tasks.show', $task) }}" aria-label="Open task"><i class="fa-solid fa-eye"></i></a><a class="btn btn-sm btn-outline-primary" href="{{ route('company-admin.tasks.edit', $task) }}" aria-label="Edit task"><i class="fa-solid fa-pen"></i></a></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="empty-cell">@include('partials.empty-state', ['icon' => 'fa-list-check', 'title' => 'No tasks found', 'message' => 'Try changing your search or filters.'])</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $tasks->links() }}
</section>
@endsection
