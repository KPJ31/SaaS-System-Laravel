@extends('layouts.app')

@section('title', ($task->exists ? 'Edit' : 'Create').' Task - Elevanix')

@section('content')
@include('partials.page-header', [
    'eyebrow' => 'Tasks',
    'title' => $task->exists ? 'Edit Task' : 'Create Task',
    'description' => $task->exists ? 'Update task details, assignment and workflow state.' : 'Create a task inside an existing project.',
])

<form method="POST" action="{{ $task->exists ? route('company-admin.tasks.update', $task) : route('company-admin.tasks.store') }}" data-loading-form>
    @csrf
    @if($task->exists)
        @method('PUT')
    @endif

    <div class="content-grid mb-3">
        <section class="content-card">
            <div class="content-card-header"><div><h2>Task Information</h2><p>Title, type and description.</p></div></div>
            <div class="row g-3">
                <div class="col-12"><label class="form-label" for="title">Title <span class="required-mark">*</span></label><input class="form-control" id="title" name="title" value="{{ old('title', $task->title) }}" required></div>
                <div class="col-md-6"><label class="form-label" for="task_type">Type</label><select class="form-select" id="task_type" name="task_type">@foreach($types as $type)<option value="{{ $type }}" @selected(old('task_type', $task->task_type ?: 'task') === $type)>{{ ucfirst($type) }}</option>@endforeach</select></div>
                <div class="col-md-6"><label class="form-label" for="priority">Priority</label><select class="form-select" id="priority" name="priority">@foreach($priorities as $priority)<option value="{{ $priority }}" @selected(old('priority', $task->priority ?: 'medium') === $priority)>{{ ucfirst($priority) }}</option>@endforeach</select></div>
                <div class="col-12"><label class="form-label" for="description">Description</label><textarea class="form-control" id="description" name="description" rows="6">{{ old('description', $task->description) }}</textarea></div>
            </div>
        </section>

        <section class="content-card">
            <div class="content-card-header"><div><h2>Assignment</h2><p>Project ownership and responsible employee.</p></div></div>
            <div class="row g-3">
                <div class="col-12"><label class="form-label" for="project_id">Project <span class="required-mark">*</span></label><select class="form-select" id="project_id" name="project_id" required><option value="">Choose project</option>@foreach($projects as $project)<option value="{{ $project->id }}" @selected((int) old('project_id', $task->project_id) === $project->id)>{{ $project->name }}</option>@endforeach</select></div>
                <div class="col-12"><label class="form-label" for="assignee_id">Assignee</label><select class="form-select" id="assignee_id" name="assignee_id"><option value="">Unassigned</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected((int) old('assignee_id', $task->assignee_id) === $employee->id)>{{ $employee->name }}{{ $employee->job_title ? ' - '.$employee->job_title : '' }}</option>@endforeach</select></div>
                <div class="col-md-6"><label class="form-label" for="status">Status</label><select class="form-select" id="status" name="status">@foreach($statuses as $status)<option value="{{ $status }}" @selected(old('status', $task->status ?: 'todo') === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>@endforeach</select></div>
                <div class="col-md-6"><label class="form-label" for="progress">Progress</label><input class="form-control" id="progress" type="number" min="0" max="100" name="progress" value="{{ old('progress', $task->progress ?? 0) }}"></div>
            </div>
        </section>
    </div>

    <section class="content-card">
        <div class="content-card-header"><div><h2>Timeline</h2><p>Dates and effort estimate.</p></div></div>
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label" for="start_date">Start Date</label><input class="form-control" id="start_date" type="date" name="start_date" value="{{ old('start_date', optional($task->start_date)->format('Y-m-d')) }}"></div>
            <div class="col-md-4"><label class="form-label" for="due_date">Due Date</label><input class="form-control" id="due_date" type="date" name="due_date" value="{{ old('due_date', optional($task->due_date)->format('Y-m-d')) }}"></div>
            <div class="col-md-4"><label class="form-label" for="estimated_hours">Estimated Hours</label><input class="form-control" id="estimated_hours" type="number" step="0.25" min="0" name="estimated_hours" value="{{ old('estimated_hours', $task->estimated_hours) }}"></div>
        </div>
        <div class="mt-4 d-flex flex-wrap gap-2"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i>Save task</button><a class="btn btn-outline-primary" href="{{ $task->exists ? route('company-admin.tasks.show', $task) : route('company-admin.tasks.index') }}">Cancel</a></div>
    </section>
</form>
@endsection
