@extends('layouts.app')

@section('title', 'Task Kanban - Elevanix')

@section('content')
@include('partials.page-header', [
    'eyebrow' => 'Tasks',
    'title' => 'Task Kanban',
    'description' => 'Review workflow stages without changing stored task statuses.',
    'actions' => new \Illuminate\Support\HtmlString('<a class="btn btn-outline-primary" href="'.route('company-admin.tasks.index').'"><i class="fa-solid fa-table-list"></i>List</a><a class="btn btn-primary" href="'.route('company-admin.tasks.create').'"><i class="fa-solid fa-plus"></i>New Task</a>'),
])

<section class="content-card mb-3">
    <form class="row g-2" method="GET">
        <div class="col-md-3"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Search tasks" aria-label="Search tasks"></div>
        <div class="col-md-3"><select class="form-select" name="project_id" aria-label="Project"><option value="">All projects</option>@foreach($projects as $project)<option value="{{ $project->id }}" @selected((int) request('project_id') === $project->id)>{{ $project->name }}</option>@endforeach</select></div>
        <div class="col-md-3"><select class="form-select" name="assignee_id" aria-label="Assignee"><option value="">All employees</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected((int) request('assignee_id') === $employee->id)>{{ $employee->name }}</option>@endforeach</select></div>
        <div class="col-md-2"><select class="form-select" name="priority" aria-label="Priority"><option value="">All priorities</option>@foreach(['low','medium','high','urgent'] as $priority)<option value="{{ $priority }}" @selected(request('priority') === $priority)>{{ ucfirst($priority) }}</option>@endforeach</select></div>
        <div class="col-md-1"><button class="btn btn-primary w-100" type="submit" aria-label="Filter"><i class="fa-solid fa-filter"></i></button></div>
    </form>
</section>

<section class="kanban-board" aria-label="Task Kanban board">
    @foreach(['todo' => 'Todo', 'in_progress' => 'In Progress', 'review' => 'Review', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $group => $label)
        @php($targetStatus = ['todo' => 'todo', 'in_progress' => 'in_progress', 'review' => 'submitted', 'completed' => 'completed', 'cancelled' => 'cancelled'][$group])
        <div class="kanban-column" data-kanban-column data-kanban-status="{{ $targetStatus }}">
            <div class="kanban-column-header"><h2>{{ $label }}</h2><span>{{ $tasksByGroup[$group]->count() }}</span></div>
            <div class="kanban-list">
                @forelse($tasksByGroup[$group] as $task)
                    <article class="kanban-card" draggable="true" data-kanban-card data-move-url="{{ route('company-admin.tasks.move', $task) }}" data-csrf="{{ csrf_token() }}">
                        <div class="d-flex justify-content-between gap-2">
                            <strong><a href="{{ route('company-admin.tasks.show', $task) }}">{{ $task->title }}</a></strong>
                            @include('partials.priority-badge', ['priority' => $task->priority])
                        </div>
                        <small>{{ $task->project?->name ?? '-' }}</small>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <span>{{ $task->assignee?->name ?? 'Unassigned' }}</span>
                            @include('partials.status-badge', ['status' => $task->status])
                        </div>
                        @if($task->due_date)
                            <small>{{ $task->due_date->format('M d, Y') }} @if($task->isOverdue()) - Overdue @endif</small>
                        @endif
                        @php($nextStatuses = $workflow->availableTransitions($task, auth()->user()))
                        @if($nextStatuses)
                            <form method="POST" action="{{ route('company-admin.tasks.move', $task) }}" class="mt-2">
                                @csrf
                                @method('PATCH')
                                <div class="input-group input-group-sm">
                                    <select class="form-select" name="status" aria-label="Move task">
                                        @foreach($nextStatuses as $status)
                                            <option value="{{ $status }}">{{ $workflow->label($status) }}</option>
                                        @endforeach
                                    </select>
                                    <button class="btn btn-outline-primary" type="submit">Move</button>
                                </div>
                            </form>
                        @endif
                    </article>
                @empty
                    <div class="kanban-empty">No tasks</div>
                @endforelse
            </div>
        </div>
    @endforeach
</section>
@endsection
