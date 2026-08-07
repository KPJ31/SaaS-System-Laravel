@extends('layouts.app')

@section('title', $project->name.' - Elevanix')

@section('content')
@include('partials.page-header', [
    'eyebrow' => 'Project Workspace',
    'title' => $project->name,
    'description' => $project->description,
])

<div class="stat-grid mb-3">
    @include('partials.stat-card', ['label' => 'Progress', 'value' => (int) $project->progress.'%', 'icon' => 'fa-bars-progress', 'tone' => 'green'])
    @include('partials.stat-card', ['label' => 'My Open Tasks', 'value' => $taskSummary['open'], 'icon' => 'fa-list-check', 'tone' => 'blue'])
    @include('partials.stat-card', ['label' => 'My Overdue Tasks', 'value' => $taskSummary['overdue'], 'icon' => 'fa-triangle-exclamation', 'tone' => 'danger'])
    @include('partials.stat-card', ['label' => 'My Hours', 'value' => number_format($hours, 1), 'icon' => 'fa-clock'])
</div>

<div class="content-grid mb-3">
    <section class="content-card">
        <div class="content-card-header">
            <div>
                <h2>Overview</h2>
                <p>Project status and delivery dates.</p>
            </div>
        </div>
        <dl class="detail-list mt-3">
            <dt>Status</dt><dd>@include('partials.status-badge', ['status' => $project->status])</dd>
            <dt>Priority</dt><dd>@include('partials.priority-badge', ['priority' => $project->priority])</dd>
            <dt>Start</dt><dd>{{ $project->start_date?->format('M d, Y') ?? '-' }}</dd>
            <dt>Due</dt><dd>{{ $project->due_date?->format('M d, Y') ?? '-' }}</dd>
            <dt>Company</dt><dd>{{ $project->company?->name ?? auth()->user()->company?->name }}</dd>
            <dt>My Tasks</dt><dd>{{ $taskSummary['completed'] }} completed out of {{ $taskSummary['total'] }}</dd>
        </dl>
        <div class="mt-3">
            <div class="progress">
                <div class="progress-bar" style="width: {{ (int) $project->progress }}%"></div>
            </div>
            <small>{{ (int) $project->progress }}% project progress</small>
        </div>
    </section>

    <section class="content-card">
        <div class="content-card-header">
            <div>
                <h2>Team Members</h2>
                <p>People assigned to this project.</p>
            </div>
        </div>
        <div class="activity-list mt-3">
            @forelse($project->users as $member)
                <div>
                    <strong>{{ $member->name }}</strong>
                    <span>{{ $member->job_title ?? ucfirst(str_replace('_', ' ', $member->role)) }}</span>
                </div>
            @empty
                @include('partials.empty-state', ['icon' => 'fa-users', 'title' => 'No team members', 'message' => 'Team assignments appear here.'])
            @endforelse
        </div>
    </section>
</div>

<section class="content-card mb-3">
    <div class="content-card-header">
        <div>
            <h2>My Project Tasks</h2>
            <p>Your assigned tasks inside this project.</p>
        </div>
        <a class="btn btn-sm btn-outline-primary" href="{{ route('employee.tasks.index', ['project_id' => $project->id]) }}">View tasks</a>
    </div>
    @include('employee.tasks._table', ['tasks' => $tasks])
</section>

<section class="content-card">
    <div class="content-card-header">
        <div>
            <h2>Project Documents</h2>
            <p>Files attached to this project.</p>
        </div>
        @if($taskSummary['total'] > 0)
            <a class="btn btn-sm btn-outline-primary" href="{{ route('employee.documents.index', ['project_id' => $project->id]) }}">Browse</a>
        @endif
    </div>
    <div class="activity-list">
        @forelse($project->files as $file)
            <div>
                <strong>{{ $file->original_name }}</strong>
                <span>Uploaded by {{ $file->uploader?->name ?? 'System' }} &middot; {{ $file->created_at->format('M d, Y') }}</span>
                <a class="btn btn-sm btn-outline-primary" href="{{ route('employee.files.download', $file) }}">Download</a>
            </div>
        @empty
            @include('partials.empty-state', ['icon' => 'fa-folder-open', 'title' => 'No documents', 'message' => 'Project files you can access appear here.'])
        @endforelse
    </div>
</section>
@endsection
