@extends('layouts.app')

@section('title', $project->name.' - Elevanix')

@section('content')
@php($currency = auth()->user()->company?->setting?->currency ?? 'USD')
@include('partials.page-header', [
    'eyebrow' => 'Project Workspace',
    'title' => $project->name,
    'description' => ($project->client?->name ?? 'Internal project').' - '.str_replace('_', ' ', ucfirst($project->status)),
    'actions' => new \Illuminate\Support\HtmlString('<a class="btn btn-outline-primary" href="'.route('company-admin.tasks.index', ['project_id' => $project->id]).'"><i class="fa-solid fa-list-check"></i>Tasks</a><a class="btn btn-primary" href="'.route('company-admin.projects.edit', $project).'"><i class="fa-solid fa-pen"></i>Edit</a>'),
])

<div class="stat-grid mb-3">
    @include('partials.stat-card', ['label' => 'Progress', 'value' => $progressValue.'%', 'icon' => 'fa-bars-progress', 'tone' => 'green'])
    @include('partials.stat-card', ['label' => 'Open Tasks', 'value' => $taskSummary['open'], 'icon' => 'fa-list-check', 'tone' => 'blue'])
    @include('partials.stat-card', ['label' => 'Overdue Tasks', 'value' => $taskSummary['overdue'], 'icon' => 'fa-triangle-exclamation', 'tone' => 'danger'])
    @include('partials.stat-card', ['label' => 'Tracked Hours', 'value' => number_format($timeSummary['hours'], 1), 'icon' => 'fa-clock'])
</div>

<div class="content-grid mb-3">
    <section class="content-card">
        <div class="content-card-header">
            <div>
                <h2>Overview</h2>
                <p>Delivery status, ownership and contract context.</p>
            </div>
        </div>
        <dl class="detail-list mt-3">
            <dt>Status</dt><dd>@include('partials.status-badge', ['status' => $project->status])</dd>
            <dt>Priority</dt><dd>@include('partials.priority-badge', ['priority' => $project->priority ?? 'medium'])</dd>
            <dt>Client</dt><dd>{{ $project->client?->name ?? 'Internal' }}</dd>
            <dt>Manager</dt><dd>{{ $project->manager?->name ?? 'Unassigned' }}</dd>
            <dt>Request</dt>
            <dd>
                @if($project->projectRequest)
                    <a href="{{ route('company-admin.project-requests.show', $project->projectRequest) }}">{{ $project->projectRequest->title }}</a>
                @else
                    -
                @endif
            </dd>
            <dt>Start</dt><dd>{{ $project->start_date?->format('M d, Y') ?? '-' }}</dd>
            <dt>Due</dt><dd>{{ $project->due_date?->format('M d, Y') ?? '-' }}</dd>
            <dt>Promised End</dt><dd>{{ $project->promised_end_date?->format('M d, Y') ?? '-' }}</dd>
            <dt>Budget</dt><dd>{{ $currency }} {{ number_format((float) ($project->budget ?? 0), 2) }}</dd>
        </dl>
        <div class="mt-3">
            <div class="progress">
                <div class="progress-bar" style="width: {{ $progressValue }}%"></div>
            </div>
            <small>{{ $progressValue }}% complete based on completed tasks.</small>
        </div>
    </section>

    <section class="content-card">
        <div class="content-card-header">
            <div>
                <h2>Team</h2>
                <p>Assign active employees to this project.</p>
            </div>
        </div>
        <form class="row g-2 mt-1" method="POST" action="{{ route('company-admin.projects.assign', $project) }}">
            @csrf
            <div class="col">
                <select class="form-select" name="user_id" required>
                    <option value="">Choose employee</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}">{{ $employee->name }}{{ $employee->job_title ? ' - '.$employee->job_title : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-primary" type="submit" title="Assign employee"><i class="fa-solid fa-user-plus"></i></button>
            </div>
        </form>
        <div class="activity-list mt-3">
            @forelse($project->users as $employee)
                <div>
                    <strong>{{ $employee->name }}</strong>
                    <span>{{ $employee->job_title ?? 'Employee' }}</span>
                    <form method="POST" action="{{ route('company-admin.projects.employees.destroy', [$project, $employee]) }}" data-confirm="Remove this employee from the project?">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger" type="submit">Remove</button>
                    </form>
                </div>
            @empty
                @include('partials.empty-state', ['icon' => 'fa-users', 'title' => 'No team assigned', 'message' => 'Assigned employees appear here.'])
            @endforelse
        </div>
    </section>
</div>

@if($project->description || $project->notes)
    <div class="content-grid mb-3">
        <section class="content-card">
            <div class="content-card-header"><div><h2>Description</h2><p>Client-facing delivery context.</p></div></div>
            <p class="mb-0">{{ $project->description ?: '-' }}</p>
        </section>
        <section class="content-card">
            <div class="content-card-header"><div><h2>Internal Notes</h2><p>Private project notes for administrators.</p></div></div>
            <p class="mb-0">{{ $project->notes ?: '-' }}</p>
        </section>
    </div>
@endif

<div class="content-grid mb-3">
    <section class="content-card">
        <div class="content-card-header">
            <div>
                <h2>Task Progress</h2>
                <p>{{ $taskSummary['completed'] }} completed out of {{ $taskSummary['total'] }} tasks.</p>
            </div>
            <a class="btn btn-sm btn-outline-primary" href="{{ route('company-admin.tasks.index', ['project_id' => $project->id]) }}">View all</a>
        </div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Task</th><th>Assignee</th><th>Status</th><th>Due</th><th class="text-end">Progress</th></tr></thead>
                <tbody>
                    @forelse($tasks as $task)
                        <tr>
                            <td><a href="{{ route('company-admin.tasks.show', $task) }}">{{ $task->title }}</a><small>{{ ucfirst($task->priority ?? 'medium') }}</small></td>
                            <td>{{ $task->assignee?->name ?? 'Unassigned' }}</td>
                            <td>@include('partials.status-badge', ['status' => $task->status])</td>
                            <td>{{ $task->due_date?->format('M d, Y') ?? '-' }}</td>
                            <td class="text-end">{{ (int) $task->progress }}%</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty-cell">@include('partials.empty-state', ['icon' => 'fa-list-check', 'title' => 'No tasks', 'message' => 'Project tasks appear here.'])</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="content-card">
        <div class="content-card-header">
            <div>
                <h2>Documents</h2>
                <p>Project-level files and recent uploads.</p>
            </div>
            <a class="btn btn-sm btn-outline-primary" href="{{ route('company-admin.documents.index', ['project_id' => $project->id]) }}">Browse</a>
        </div>
        <form class="row g-2 mb-3" method="POST" action="{{ route('company-admin.documents.store') }}" enctype="multipart/form-data" data-loading-form>
            @csrf
            <input type="hidden" name="project_id" value="{{ $project->id }}">
            <div class="col">
                <input class="form-control" type="file" name="file" required>
            </div>
            <div class="col-auto">
                <button class="btn btn-primary" type="submit" title="Upload document"><i class="fa-solid fa-upload"></i></button>
            </div>
        </form>
        <div class="activity-list">
            @forelse($documents as $file)
                <div>
                    <strong>{{ $file->original_name }}</strong>
                    <span>{{ number_format(($file->size ?? 0) / 1024, 1) }} KB &middot; {{ $file->uploader?->name ?? 'System' }} &middot; {{ $file->created_at->format('M d, Y') }}</span>
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('company-admin.documents.download', $file) }}">Download</a>
                </div>
            @empty
                @include('partials.empty-state', ['icon' => 'fa-folder-open', 'title' => 'No documents', 'message' => 'Uploaded project documents appear here.'])
            @endforelse
        </div>
    </section>
</div>

<div class="content-grid">
    <section class="content-card">
        <div class="content-card-header">
            <div>
                <h2>Time</h2>
                <p>{{ $timeSummary['sessions'] }} sessions, {{ $timeSummary['running'] }} currently running.</p>
            </div>
        </div>
        <div class="activity-list">
            @forelse($workSessions as $session)
                <div>
                    <strong>{{ $session->user?->name ?? 'Unknown user' }}</strong>
                    <span>{{ $session->task?->title ?? 'Project work' }} &middot; {{ number_format(($session->duration_minutes ?? 0) / 60, 1) }} hours &middot; {{ $session->started_at?->format('M d, H:i') }}</span>
                </div>
            @empty
                @include('partials.empty-state', ['icon' => 'fa-clock', 'title' => 'No time entries', 'message' => 'Tracked project time appears here.'])
            @endforelse
        </div>
    </section>

    <section class="content-card">
        <div class="content-card-header">
            <div>
                <h2>Finance</h2>
                <p>Invoices and project-linked payments.</p>
            </div>
        </div>
        <dl class="detail-list mt-3">
            <dt>Invoice Total</dt><dd>{{ $currency }} {{ number_format($financeSummary['invoice_total'], 2) }}</dd>
            <dt>Invoice Balance</dt><dd>{{ $currency }} {{ number_format($financeSummary['invoice_balance'], 2) }}</dd>
            <dt>Paid Payments</dt><dd>{{ $currency }} {{ number_format($financeSummary['paid_payments'], 2) }}</dd>
            <dt>Pending Payments</dt><dd>{{ $financeSummary['pending_payments'] }}</dd>
        </dl>
    </section>

    <section class="content-card">
        <div class="content-card-header">
            <div>
                <h2>Activity</h2>
                <p>Recent project and task activity.</p>
            </div>
        </div>
        <div class="activity-list">
            @forelse($activityLogs as $log)
                <div>
                    <strong>{{ str_replace('_', ' ', ucfirst($log->action)) }}</strong>
                    <span>{{ $log->description }} &middot; {{ $log->user?->name ?? 'System' }} &middot; {{ $log->created_at->format('M d, Y H:i') }}</span>
                </div>
            @empty
                @include('partials.empty-state', ['icon' => 'fa-clipboard-list', 'title' => 'No activity', 'message' => 'Tracked project activity appears here.'])
            @endforelse
        </div>
    </section>
</div>
@endsection
