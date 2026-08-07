@extends('layouts.app')

@section('title', $employee->name.' - Elevanix')

@section('content')
@include('partials.page-header', [
    'breadcrumbs' => [
        ['label' => 'Dashboard', 'url' => route('company-admin.dashboard')],
        ['label' => 'Employees', 'url' => route('company-admin.employees.index')],
        ['label' => $employee->name],
    ],
    'eyebrow' => 'Employee Overview',
    'title' => $employee->name,
    'description' => trim(($employee->job_title ?? 'Employee').' | '.$employee->email),
    'actions' => new \Illuminate\Support\HtmlString('<a class="btn btn-outline-primary" href="'.route('company-admin.employees.permissions.edit', $employee).'"><i class="fa-solid fa-shield-halved"></i>Permissions</a><a class="btn btn-primary" href="'.route('company-admin.employees.edit', $employee).'"><i class="fa-solid fa-pen"></i>Edit</a>'),
])

<div class="stat-grid mb-3">
    @include('partials.stat-card', ['label' => 'Assigned Projects', 'value' => $employee->projects_count, 'icon' => 'fa-diagram-project', 'tone' => 'blue'])
    @include('partials.stat-card', ['label' => 'Open Tasks', 'value' => $employee->open_tasks_count, 'icon' => 'fa-list-check', 'tone' => 'green'])
    @include('partials.stat-card', ['label' => 'Overdue Tasks', 'value' => $employee->overdue_tasks_count, 'icon' => 'fa-triangle-exclamation', 'tone' => 'danger'])
    @include('partials.stat-card', ['label' => 'Extra Permissions', 'value' => $employee->permissions_count, 'icon' => 'fa-shield-halved', 'tone' => 'yellow'])
</div>

<div class="content-grid mb-3">
    <section class="content-card">
        <div class="content-card-header"><div><h2>Profile</h2><p>Account and employment information.</p></div></div>
        <dl class="detail-list mt-3">
            <dt>Status</dt><dd>@include('partials.status-badge', ['status' => $employee->status])</dd>
            <dt>Employee Code</dt><dd>{{ $employee->employee_code ?? '-' }}</dd>
            <dt>Job Title</dt><dd>{{ $employee->job_title ?? '-' }}</dd>
            <dt>Department</dt><dd>{{ $employee->department ?? '-' }}</dd>
            <dt>Email</dt><dd>{{ $employee->email }}</dd>
            <dt>Phone</dt><dd>{{ $employee->phone ?? '-' }}</dd>
            <dt>Joined</dt><dd>{{ $employee->join_date?->format('Y-m-d') ?? '-' }}</dd>
            <dt>Last Login</dt><dd>{{ $employee->last_login_at?->format('Y-m-d H:i') ?? '-' }}</dd>
            <dt>Address</dt><dd>{{ $employee->address ?? '-' }}</dd>
        </dl>
    </section>

    <section class="content-card">
        <div class="content-card-header"><div><h2>Access and Account</h2><p>Workspace access controls for this employee.</p></div></div>
        <div class="d-flex flex-wrap gap-2 mt-3">
            @foreach(['active','suspended','inactive'] as $status)
                <form method="POST" action="{{ route('company-admin.employees.status', [$employee, $status]) }}" data-confirm="Change employee status?" data-confirm-text="This changes whether the employee can use the company workspace.">
                    @csrf
                    <button class="btn btn-outline-primary" type="submit">{{ ucfirst($status) }}</button>
                </form>
            @endforeach
            <form method="POST" action="{{ route('company-admin.employees.password-reset', $employee) }}">
                @csrf
                <button class="btn btn-outline-primary" type="submit"><i class="fa-solid fa-key"></i>Password reset</button>
            </form>
        </div>
    </section>
</div>

<div class="content-grid mb-3">
    <section class="content-card">
        <div class="content-card-header">
            <div><h2>Assigned Projects</h2><p>Recent projects connected to this employee.</p></div>
            <a class="btn btn-sm btn-outline-primary" href="{{ route('company-admin.projects.index') }}"><i class="fa-solid fa-folder-open"></i>Projects</a>
        </div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Project</th><th>Status</th><th>Tasks</th><th>Due</th></tr></thead>
                <tbody>
                    @forelse($projects as $project)
                        <tr>
                            <td><a class="fw-semibold" href="{{ route('company-admin.projects.show', $project) }}">{{ $project->name }}</a><small>{{ $project->client?->name ?? 'Internal' }}</small></td>
                            <td>@include('partials.status-badge', ['status' => $project->status])</td>
                            <td>{{ $project->tasks_count }}</td>
                            <td>{{ $project->due_date?->format('Y-m-d') ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty-cell">@include('partials.empty-state', ['icon' => 'fa-folder-open', 'title' => 'No projects assigned', 'message' => 'Project assignments will appear here.'])</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="content-card">
        <div class="content-card-header">
            <div><h2>Current Tasks</h2><p>Latest assigned task activity.</p></div>
            <a class="btn btn-sm btn-outline-primary" href="{{ route('company-admin.tasks.index', ['assignee_id' => $employee->id]) }}"><i class="fa-solid fa-list-check"></i>Tasks</a>
        </div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Task</th><th>Status</th><th>Priority</th><th>Due</th></tr></thead>
                <tbody>
                    @forelse($tasks as $task)
                        <tr>
                            <td><a class="fw-semibold" href="{{ route('company-admin.tasks.show', $task) }}">{{ $task->title }}</a><small>{{ $task->project?->name }}</small></td>
                            <td>@include('partials.status-badge', ['status' => $task->status])</td>
                            <td>@include('partials.priority-badge', ['priority' => $task->priority])</td>
                            <td>{{ $task->due_date?->format('Y-m-d') ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty-cell">@include('partials.empty-state', ['icon' => 'fa-list-check', 'title' => 'No tasks assigned', 'message' => 'Assigned tasks will appear here.'])</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

<div class="content-grid">
    <section class="content-card">
        <div class="content-card-header"><div><h2>Attendance</h2><p>Recent attendance records.</p></div></div>
        <div class="activity-list">
            @forelse($recentAttendances as $attendance)
                <div>
                    <strong>{{ $attendance->attendance_date->format('Y-m-d') }}</strong>
                    <span>{{ $attendance->check_in_time?->format('H:i') ?? '--:--' }} - {{ $attendance->check_out_time?->format('H:i') ?? '--:--' }} @include('partials.status-badge', ['status' => $attendance->status])</span>
                </div>
            @empty
                @include('partials.empty-state', ['icon' => 'fa-calendar-days', 'title' => 'No attendance records', 'message' => 'Recent attendance will appear here.'])
            @endforelse
        </div>
    </section>

    <section class="content-card">
        <div class="content-card-header"><div><h2>Work and Leave</h2><p>Recent work sessions and leave requests.</p></div></div>
        <div class="activity-list">
            @forelse($recentWorkSessions as $session)
                <div>
                    <strong>{{ $session->task?->title ?? $session->project?->name ?? 'Work session' }}</strong>
                    <span>{{ $session->started_at?->format('Y-m-d H:i') }} &middot; {{ number_format(($session->duration_minutes ?? 0) / 60, 1) }} hours</span>
                </div>
            @empty
                @include('partials.empty-state', ['icon' => 'fa-clock', 'title' => 'No work sessions', 'message' => 'Recent time entries will appear here.'])
            @endforelse
            @foreach($recentLeaveRequests as $leave)
                <div>
                    <strong>{{ ucfirst($leave->leave_type) }} leave</strong>
                    <span>{{ $leave->start_date->format('Y-m-d') }} to {{ $leave->end_date->format('Y-m-d') }} @include('partials.status-badge', ['status' => $leave->status])</span>
                </div>
            @endforeach
        </div>
    </section>
</div>
@endsection
