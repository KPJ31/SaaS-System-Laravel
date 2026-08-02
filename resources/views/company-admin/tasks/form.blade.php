@extends('layouts.app')

@section('title', ($task->exists ? 'Edit' : 'Create').' Task - Elevanix')

@section('content')
@include('partials.page-header', ['eyebrow' => 'Tasks', 'title' => $task->exists ? 'Edit Task' : 'Create Task'])
<form class="content-card" method="POST" action="{{ $task->exists ? route('company-admin.tasks.update', $task) : route('company-admin.tasks.store') }}" data-loading-form>
    @csrf @if($task->exists) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Title <span class="required-mark">*</span></label><input class="form-control" name="title" value="{{ old('title', $task->title) }}" required></div>
        <div class="col-md-6"><label class="form-label">Project <span class="required-mark">*</span></label><select class="form-select" name="project_id" required><option value="">Choose project</option>@foreach($projects as $project)<option value="{{ $project->id }}" @selected(old('project_id', $task->project_id)==$project->id)>{{ $project->name }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label">Assignee</label><select class="form-select" name="assignee_id"><option value="">Unassigned</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected(old('assignee_id', $task->assignee_id)==$employee->id)>{{ $employee->name }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label">Priority</label><select class="form-select" name="priority">@foreach(['low','medium','high','urgent'] as $priority)<option value="{{ $priority }}" @selected(old('priority', $task->priority ?: 'medium')===$priority)>{{ ucfirst($priority) }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label">Status</label><select class="form-select" name="status">@foreach(['todo','assigned','in_progress','paused','submitted','under_review','completed','cancelled'] as $status)<option value="{{ $status }}" @selected(old('status', $task->status ?: 'todo')===$status)>{{ str_replace('_',' ',ucfirst($status)) }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label">Type</label><select class="form-select" name="task_type">@foreach(['task','bug','issue','improvement'] as $type)<option value="{{ $type }}" @selected(old('task_type', $task->task_type ?: 'task')===$type)>{{ ucfirst($type) }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label">Progress</label><input class="form-control" type="number" min="0" max="100" name="progress" value="{{ old('progress', $task->progress ?? 0) }}"></div>
        <div class="col-md-4"><label class="form-label">Start Date</label><input class="form-control" type="date" name="start_date" value="{{ old('start_date', optional($task->start_date)->format('Y-m-d')) }}"></div>
        <div class="col-md-4"><label class="form-label">Due Date</label><input class="form-control" type="date" name="due_date" value="{{ old('due_date', optional($task->due_date)->format('Y-m-d')) }}"></div>
        <div class="col-md-4"><label class="form-label">Estimated Hours</label><input class="form-control" type="number" step="0.25" name="estimated_hours" value="{{ old('estimated_hours', $task->estimated_hours) }}"></div>
        <div class="col-md-12"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="4">{{ old('description', $task->description) }}</textarea></div>
    </div>
    <div class="mt-4 d-flex gap-2"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i>Save task</button><a class="btn btn-outline-primary" href="{{ route('company-admin.tasks.index') }}">Cancel</a></div>
</form>
@endsection
