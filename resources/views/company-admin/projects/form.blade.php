@extends('layouts.app')

@section('title', ($project->exists ? 'Edit' : 'Create').' Project - Elevanix')

@section('content')
@include('partials.page-header', [
    'eyebrow' => 'Projects',
    'title' => $project->exists ? 'Edit Project' : 'Create Project',
    'description' => $project->exists ? 'Update delivery details, ownership and team assignments.' : 'Create a project and staff the initial delivery team.',
])

@php
    $selectedTeam = collect(old('team_member_ids', $project->exists ? $project->users->pluck('id')->all() : []))->map(fn ($id) => (int) $id)->all();
@endphp

<form method="POST" action="{{ $project->exists ? route('company-admin.projects.update', $project) : route('company-admin.projects.store') }}" data-loading-form>
    @csrf
    @if($project->exists)
        @method('PUT')
    @endif

    <div class="content-grid mb-3">
        <section class="content-card">
            <div class="content-card-header">
                <div>
                    <h2>Project Brief</h2>
                    <p>Core project identity, client and source request.</p>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label" for="name">Project Name <span class="required-mark">*</span></label>
                    <input class="form-control" id="name" name="name" value="{{ old('name', $project->name) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="client_id">Client</label>
                    <select class="form-select" id="client_id" name="client_id">
                        <option value="">Internal project</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" @selected((int) old('client_id', $project->client_id) === $client->id)>{{ $client->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="manager_id">Manager</label>
                    <select class="form-select" id="manager_id" name="manager_id">
                        <option value="">No manager</option>
                        @foreach($managers as $manager)
                            <option value="{{ $manager->id }}" @selected((int) old('manager_id', $project->manager_id) === $manager->id)>{{ $manager->name }} - {{ str_replace('_', ' ', ucfirst($manager->role)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="project_request_id">Request</label>
                    <select class="form-select" id="project_request_id" name="project_request_id">
                        <option value="">No request</option>
                        @foreach($requests as $requestItem)
                            <option value="{{ $requestItem->id }}" @selected((int) old('project_request_id', $project->project_request_id) === $requestItem->id)>{{ $requestItem->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label" for="description">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="5">{{ old('description', $project->description) }}</textarea>
                </div>
            </div>
        </section>

        <section class="content-card">
            <div class="content-card-header">
                <div>
                    <h2>Delivery Plan</h2>
                    <p>Status, timeline, priority and budget.</p>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="status">Status</label>
                    <select class="form-select" id="status" name="status">
                        @foreach(['planning','pending','approved','active','in_progress','on_hold','testing','completed','cancelled'] as $status)
                            <option value="{{ $status }}" @selected(old('status', $project->status ?: 'planning') === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="priority">Priority</label>
                    <select class="form-select" id="priority" name="priority">
                        @foreach(['low','medium','high','urgent'] as $priority)
                            <option value="{{ $priority }}" @selected(old('priority', $project->priority ?: 'medium') === $priority)>{{ ucfirst($priority) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="start_date">Start Date</label>
                    <input class="form-control" id="start_date" type="date" name="start_date" value="{{ old('start_date', optional($project->start_date)->format('Y-m-d')) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="due_date">Due Date</label>
                    <input class="form-control" id="due_date" type="date" name="due_date" value="{{ old('due_date', optional($project->due_date)->format('Y-m-d')) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="promised_end_date">Promised End</label>
                    <input class="form-control" id="promised_end_date" type="date" name="promised_end_date" value="{{ old('promised_end_date', optional($project->promised_end_date)->format('Y-m-d')) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="completed_date">Completed Date</label>
                    <input class="form-control" id="completed_date" type="date" name="completed_date" value="{{ old('completed_date', optional($project->completed_date)->format('Y-m-d')) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="budget">Budget</label>
                    <input class="form-control" id="budget" type="number" step="0.01" min="0" name="budget" value="{{ old('budget', $project->budget) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="progress">Progress</label>
                    <input class="form-control" id="progress" type="number" min="0" max="100" name="progress" value="{{ old('progress', $project->progress ?? 0) }}">
                </div>
            </div>
        </section>
    </div>

    <section class="content-card mb-3">
        <div class="content-card-header">
            <div>
                <h2>Team</h2>
                <p>Employees selected here are synced to the project team.</p>
            </div>
        </div>
        <div class="row g-2">
            @forelse($employees as $employee)
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <label class="form-check">
                        <input class="form-check-input" type="checkbox" name="team_member_ids[]" value="{{ $employee->id }}" @checked(in_array($employee->id, $selectedTeam, true))>
                        <span class="form-check-label">{{ $employee->name }} <small>{{ $employee->job_title ?? 'Employee' }}</small></span>
                    </label>
                </div>
            @empty
                <div class="col-12">
                    @include('partials.empty-state', ['icon' => 'fa-users', 'title' => 'No active employees', 'message' => 'Active employees can be assigned after they are added.'])
                </div>
            @endforelse
        </div>
    </section>

    <section class="content-card">
        <label class="form-label" for="notes">Internal Notes</label>
        <textarea class="form-control" id="notes" name="notes" rows="4">{{ old('notes', $project->notes) }}</textarea>
        <div class="mt-4 d-flex flex-wrap gap-2">
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i>Save project</button>
            <a class="btn btn-outline-primary" href="{{ $project->exists ? route('company-admin.projects.show', $project) : route('company-admin.projects.index') }}">Cancel</a>
        </div>
    </section>
</form>
@endsection
