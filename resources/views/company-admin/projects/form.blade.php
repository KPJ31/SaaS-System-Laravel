@extends('layouts.app')

@section('title', ($project->exists ? 'Edit' : 'Create').' Project - Elevanix')

@section('content')
@include('partials.page-header', ['eyebrow' => 'Projects', 'title' => $project->exists ? 'Edit Project' : 'Create Project'])
<form class="content-card" method="POST" action="{{ $project->exists ? route('company-admin.projects.update', $project) : route('company-admin.projects.store') }}" data-loading-form>
    @csrf @if($project->exists) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-md-8"><label class="form-label">Project Name <span class="required-mark">*</span></label><input class="form-control" name="name" value="{{ old('name', $project->name) }}" required></div>
        <div class="col-md-4"><label class="form-label">Client</label><select class="form-select" name="client_id"><option value="">Internal project</option>@foreach($clients as $client)<option value="{{ $client->id }}" @selected(old('client_id', $project->client_id)==$client->id)>{{ $client->name }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label">Manager</label><select class="form-select" name="manager_id"><option value="">No manager</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected(old('manager_id', $project->manager_id)==$employee->id)>{{ $employee->name }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label">Request</label><select class="form-select" name="project_request_id"><option value="">No request</option>@foreach($requests as $request)<option value="{{ $request->id }}" @selected(old('project_request_id', $project->project_request_id)==$request->id)>{{ $request->title }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label">Status</label><select class="form-select" name="status">@foreach(['planning','pending','approved','active','in_progress','on_hold','testing','completed','cancelled'] as $status)<option value="{{ $status }}" @selected(old('status', $project->status ?: 'planning')===$status)>{{ str_replace('_',' ',ucfirst($status)) }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label">Priority</label><select class="form-select" name="priority">@foreach(['low','medium','high','urgent'] as $priority)<option value="{{ $priority }}" @selected(old('priority', $project->priority ?: 'medium')===$priority)>{{ ucfirst($priority) }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label">Start Date</label><input class="form-control" type="date" name="start_date" value="{{ old('start_date', optional($project->start_date)->format('Y-m-d')) }}"></div>
        <div class="col-md-3"><label class="form-label">Due Date</label><input class="form-control" type="date" name="due_date" value="{{ old('due_date', optional($project->due_date)->format('Y-m-d')) }}"></div>
        <div class="col-md-3"><label class="form-label">Promised End</label><input class="form-control" type="date" name="promised_end_date" value="{{ old('promised_end_date', optional($project->promised_end_date)->format('Y-m-d')) }}"></div>
        <div class="col-md-3"><label class="form-label">Completed Date</label><input class="form-control" type="date" name="completed_date" value="{{ old('completed_date', optional($project->completed_date)->format('Y-m-d')) }}"></div>
        <div class="col-md-3"><label class="form-label">Budget</label><input class="form-control" type="number" step="0.01" name="budget" value="{{ old('budget', $project->budget) }}"></div>
        <div class="col-md-3"><label class="form-label">Progress</label><input class="form-control" type="number" min="0" max="100" name="progress" value="{{ old('progress', $project->progress ?? 0) }}"></div>
        <div class="col-md-12"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="4">{{ old('description', $project->description) }}</textarea></div>
        <div class="col-md-12"><label class="form-label">Notes</label><textarea class="form-control" name="notes" rows="3">{{ old('notes', $project->notes) }}</textarea></div>
    </div>
    <div class="mt-4 d-flex gap-2"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i>Save project</button><a class="btn btn-outline-primary" href="{{ route('company-admin.projects.index') }}">Cancel</a></div>
</form>
@endsection
