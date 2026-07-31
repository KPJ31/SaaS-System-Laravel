@extends('layouts.app')

@section('title', 'Company Dashboard - Elevanix')

@section('content')
<div class="page-header welcome-banner">
    <div>
        <span>{{ auth()->user()->company->name }}</span>
        <h1>Welcome back, {{ auth()->user()->name }}.</h1>
    </div>
</div>

<div class="stat-grid">
    @include('partials.stat-card', ['label' => 'Clients', 'value' => $clientsCount, 'icon' => 'fa-handshake'])
    @include('partials.stat-card', ['label' => 'Employees', 'value' => $employeesCount, 'icon' => 'fa-users', 'tone' => 'blue'])
    @include('partials.stat-card', ['label' => 'Projects', 'value' => $projectsCount, 'icon' => 'fa-diagram-project', 'tone' => 'green'])
    @include('partials.stat-card', ['label' => 'Tasks', 'value' => $tasksCount, 'icon' => 'fa-list-check', 'tone' => 'yellow'])
</div>

<div class="content-grid">
    <section class="content-card">
        <h2>Recent Projects</h2>
        <div class="project-list">
            @forelse($projects as $project)
                <article>
                    <strong>{{ $project->name }}</strong>
                    <span>{{ $project->client?->name ?? 'Internal' }}</span>
                    <div class="progress">
                        <div class="progress-bar" style="width: {{ $project->progress }}%"></div>
                    </div>
                </article>
            @empty
                <p class="empty-cell">No projects yet.</p>
            @endforelse
        </div>
    </section>

    <section class="content-card">
        <h2>Latest Tasks</h2>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Task</th>
                        <th>Assignee</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tasks as $task)
                        <tr>
                            <td>{{ $task->title }}<small>{{ $task->project->name }}</small></td>
                            <td>{{ $task->assignee?->name ?? 'Unassigned' }}</td>
                            <td>@include('partials.status-badge', ['status' => $task->status])</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="empty-cell">No tasks yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
