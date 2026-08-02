@extends('layouts.app')

@section('title', 'Company Dashboard - Elevanix')

@section('content')
@include('partials.page-header', [
    'eyebrow' => auth()->user()->company->name,
    'title' => 'Welcome back, '.auth()->user()->name.'.',
    'description' => 'Review your company workspace, recent projects and latest assigned work.',
])

<div class="stat-grid">
    @include('partials.stat-card', ['label' => 'Clients', 'value' => $clientsCount, 'icon' => 'fa-handshake'])
    @include('partials.stat-card', ['label' => 'Employees', 'value' => $employeesCount, 'icon' => 'fa-users', 'tone' => 'blue'])
    @include('partials.stat-card', ['label' => 'Projects', 'value' => $projectsCount, 'icon' => 'fa-diagram-project', 'tone' => 'green'])
    @include('partials.stat-card', ['label' => 'Tasks', 'value' => $tasksCount, 'icon' => 'fa-list-check', 'tone' => 'yellow'])
</div>

<div class="content-grid">
    <section class="content-card">
        <div class="content-card-header">
            <div>
                <h2>Recent Projects</h2>
                <p>Latest company projects with client context and progress.</p>
            </div>
        </div>
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
                @include('partials.empty-state', ['icon' => 'fa-diagram-project', 'title' => 'No projects yet', 'message' => 'Projects created for this company will appear here.'])
            @endforelse
        </div>
    </section>

    <section class="content-card">
        <div class="content-card-header">
            <div>
                <h2>Latest Tasks</h2>
                <p>Recently created tasks across active company projects.</p>
            </div>
        </div>
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
                        <tr><td colspan="3" class="empty-cell">@include('partials.empty-state', ['icon' => 'fa-list-check', 'title' => 'No tasks yet', 'message' => 'Assigned and unassigned tasks will appear here.'])</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
