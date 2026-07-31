@extends('layouts.app')

@section('title', 'Employee Dashboard - Elevanix')

@section('content')
<div class="page-header welcome-banner">
    <div>
        <span>{{ auth()->user()->company->name }}</span>
        <h1>My Workspace</h1>
    </div>
</div>
<div class="stat-grid">
    @include('partials.stat-card', ['label' => 'Open Tasks', 'value' => $openTasksCount, 'icon' => 'fa-list-check'])
    @include('partials.stat-card', ['label' => 'Recent Sessions', 'value' => $sessions->count(), 'icon' => 'fa-clock', 'tone' => 'blue'])
</div>
<div class="content-grid">
    <section class="content-card">
        <h2>My Tasks</h2>
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
                        <tr><td colspan="4" class="empty-cell">No assigned tasks yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    <section class="content-card">
        <h2>Work Sessions</h2>
        <div class="activity-list">
            @forelse($sessions as $session)
                <div><strong>{{ $session->project?->name ?? 'General work' }}</strong><span>{{ $session->duration_minutes }} minutes</span></div>
            @empty
                <p class="empty-cell">No work sessions yet.</p>
            @endforelse
        </div>
    </section>
</div>
@endsection
