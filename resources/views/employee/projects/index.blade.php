@extends('layouts.app')

@section('title', 'My Projects - Elevanix')

@section('content')
@include('partials.page-header', [
    'eyebrow' => 'Employee',
    'title' => 'My Projects',
    'description' => 'Projects assigned to you or containing your tasks.',
])

<div class="stat-grid mb-3">
    @include('partials.stat-card', ['label' => 'Assigned', 'value' => $summary['assigned'], 'icon' => 'fa-diagram-project', 'tone' => 'blue'])
    @include('partials.stat-card', ['label' => 'Active', 'value' => $summary['active'], 'icon' => 'fa-bolt', 'tone' => 'green'])
    @include('partials.stat-card', ['label' => 'Overdue', 'value' => $summary['overdue'], 'icon' => 'fa-triangle-exclamation', 'tone' => 'danger'])
    @include('partials.stat-card', ['label' => 'Completed', 'value' => $summary['completed'], 'icon' => 'fa-circle-check'])
</div>

<section class="content-card mb-3">
    <form class="row g-2" method="GET">
        <div class="col-lg-4 col-md-6">
            <label class="form-label" for="search">Search</label>
            <input class="form-control" id="search" name="search" value="{{ request('search') }}" placeholder="Search projects">
        </div>
        <div class="col-lg-3 col-md-6">
            <label class="form-label" for="status">Status</label>
            <select class="form-select" id="status" name="status">
                <option value="">All statuses</option>
                @foreach(['planning','pending','approved','active','in_progress','on_hold','testing','completed','cancelled'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-3 col-md-6">
            <label class="form-label" for="priority">Priority</label>
            <select class="form-select" id="priority" name="priority">
                <option value="">All priorities</option>
                @foreach(['low','medium','high','urgent'] as $priority)
                    <option value="{{ $priority }}" @selected(request('priority') === $priority)>{{ ucfirst($priority) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-2 col-md-6 d-flex align-items-end gap-2">
            <button class="btn btn-primary w-100" type="submit"><i class="fa-solid fa-filter"></i>Filter</button>
            <a class="btn btn-outline-primary" href="{{ route('employee.projects.index') }}" title="Clear filters"><i class="fa-solid fa-xmark"></i></a>
        </div>
    </form>
</section>

<section class="content-card">
    <div class="content-card-header">
        <div>
            <h2>{{ $projects->total() }} Projects</h2>
            <p>Your assigned delivery portfolio.</p>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Project</th>
                    <th>Priority</th>
                    <th>Progress</th>
                    <th>My Tasks</th>
                    <th>Status</th>
                    <th>Due</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($projects as $project)
                    <tr>
                        <td>
                            <strong>{{ $project->name }}</strong>
                            <small>{{ $project->company?->name ?? auth()->user()->company?->name }}</small>
                        </td>
                        <td>@include('partials.priority-badge', ['priority' => $project->priority])</td>
                        <td style="min-width: 140px;">
                            <div class="progress">
                                <div class="progress-bar" style="width: {{ (int) $project->progress }}%"></div>
                            </div>
                            <small>{{ (int) $project->progress }}%</small>
                        </td>
                        <td>
                            {{ $project->my_open_tasks_count }} open
                            <small>{{ $project->my_tasks_count }} assigned</small>
                        </td>
                        <td>@include('partials.status-badge', ['status' => $project->status])</td>
                        <td>{{ $project->due_date?->format('M d, Y') ?? '-' }}</td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('employee.projects.show', $project) }}"><i class="fa-solid fa-eye"></i>View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="empty-cell">
                            @include('partials.empty-state', ['icon' => 'fa-diagram-project', 'title' => 'No projects', 'message' => 'Assigned projects appear here.'])
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $projects->links() }}
</section>
@endsection
