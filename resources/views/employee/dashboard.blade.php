@extends('layouts.app')

@section('title', 'Employee Dashboard - Elevanix')

@section('content')
@include('partials.page-header', [
    'eyebrow' => auth()->user()->company->name,
    'title' => 'My Workspace',
    'description' => 'Track your assigned tasks, due dates and recent work sessions.',
])
<div class="stat-grid">
    @include('partials.stat-card', ['label' => 'Open Tasks', 'value' => $openTasksCount, 'icon' => 'fa-list-check'])
    @include('partials.stat-card', ['label' => 'Recent Sessions', 'value' => $sessions->count(), 'icon' => 'fa-clock', 'tone' => 'blue'])
</div>
<div class="content-grid">
    <section class="content-card">
        <div class="content-card-header">
            <div>
                <h2>My Tasks</h2>
                <p>Current assignments connected to your company projects.</p>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Task</th><th>Project</th><th>Status</th><th>Due</th></tr></thead>
                <tbody>
                    @forelse($tasks as $task)
                        <tr>
                            <td>{{ $task->title }}</td>
                            <td>{{ $task->project->name }}</td>
                            <td>@include('partials.status-badge', ['status' => $task->status])</td>
                            <td>{{ $task->due_date?->format('M d') ?? 'No date' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty-cell">@include('partials.empty-state', ['icon' => 'fa-list-check', 'title' => 'No assigned tasks', 'message' => 'Tasks assigned to you will appear here.'])</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    <section class="content-card">
        <div class="content-card-header">
            <div>
                <h2>Work Sessions</h2>
                <p>Your latest tracked work time.</p>
            </div>
        </div>
        <div class="activity-list">
            @forelse($sessions as $session)
                <div><strong>{{ $session->project?->name ?? 'General work' }}</strong><span>{{ $session->duration_minutes }} minutes</span></div>
            @empty
                @include('partials.empty-state', ['icon' => 'fa-clock', 'title' => 'No work sessions', 'message' => 'Tracked time will appear here once sessions are recorded.'])
            @endforelse
        </div>
    </section>
</div>
@endsection
