@extends('layouts.app')

@section('title', 'Projects - Elevanix')

@section('content')
@include('partials.page-header', [
    'eyebrow' => 'Company Admin',
    'title' => 'Projects',
    'description' => 'Plan, staff and monitor delivery work across your company.',
    'actions' => new \Illuminate\Support\HtmlString('<a class="btn btn-primary" href="'.route('company-admin.projects.create').'"><i class="fa-solid fa-plus"></i>Create project</a>'),
])

<div class="stat-grid mb-3">
    @include('partials.stat-card', ['label' => 'Active Delivery', 'value' => $summary['active'], 'icon' => 'fa-bolt', 'tone' => 'green'])
    @include('partials.stat-card', ['label' => 'Planning Queue', 'value' => $summary['planning'], 'icon' => 'fa-clipboard-list', 'tone' => 'blue'])
    @include('partials.stat-card', ['label' => 'Overdue', 'value' => $summary['overdue'], 'icon' => 'fa-triangle-exclamation', 'tone' => 'danger'])
    @include('partials.stat-card', ['label' => 'Completed', 'value' => $summary['completed'], 'icon' => 'fa-circle-check'])
</div>

<section class="content-card mb-3">
    <form class="row g-3" method="GET">
        <div class="col-lg-3 col-md-6">
            <label class="form-label" for="search">Search</label>
            <input class="form-control" id="search" name="search" value="{{ request('search') }}" placeholder="Project, client or manager">
        </div>
        <div class="col-lg-2 col-md-6">
            <label class="form-label" for="status">Status</label>
            <select class="form-select" id="status" name="status">
                <option value="">All statuses</option>
                @foreach(['planning','pending','approved','active','in_progress','on_hold','testing','completed','cancelled'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-2 col-md-6">
            <label class="form-label" for="priority">Priority</label>
            <select class="form-select" id="priority" name="priority">
                <option value="">All priorities</option>
                @foreach(['low','medium','high','urgent'] as $priority)
                    <option value="{{ $priority }}" @selected(request('priority') === $priority)>{{ ucfirst($priority) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-2 col-md-6">
            <label class="form-label" for="client_id">Client</label>
            <select class="form-select" id="client_id" name="client_id">
                <option value="">All clients</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}" @selected((int) request('client_id') === $client->id)>{{ $client->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-2 col-md-6">
            <label class="form-label" for="manager_id">Manager</label>
            <select class="form-select" id="manager_id" name="manager_id">
                <option value="">All managers</option>
                @foreach($managers as $manager)
                    <option value="{{ $manager->id }}" @selected((int) request('manager_id') === $manager->id)>{{ $manager->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-1 col-md-6">
            <label class="form-label" for="deadline">Due</label>
            <select class="form-select" id="deadline" name="deadline">
                <option value="">Any</option>
                <option value="overdue" @selected(request('deadline') === 'overdue')>Late</option>
                <option value="upcoming" @selected(request('deadline') === 'upcoming')>Soon</option>
            </select>
        </div>
        <div class="col-12 d-flex flex-wrap gap-2">
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter"></i>Filter</button>
            <a class="btn btn-outline-primary" href="{{ route('company-admin.projects.index') }}">Clear</a>
        </div>
    </form>
</section>

<section class="content-card">
    <div class="content-card-header">
        <div>
            <h2>{{ $projects->total() }} Projects</h2>
            <p>Portfolio health, staffing and deadline pressure.</p>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Project</th>
                    <th>Owner</th>
                    <th>Progress</th>
                    <th>Workload</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($projects as $project)
                    <tr>
                        <td>
                            <strong>{{ $project->name }}</strong>
                            <small>{{ $project->client?->name ?? 'Internal' }} &middot; {{ ucfirst($project->priority ?? 'medium') }}</small>
                        </td>
                        <td>
                            {{ $project->manager?->name ?? 'Unassigned' }}
                            <small>{{ $project->team_count }} team members</small>
                        </td>
                        <td style="min-width: 140px;">
                            <div class="progress">
                                <div class="progress-bar" style="width: {{ (int) $project->progress }}%"></div>
                            </div>
                            <small>{{ (int) $project->progress }}%</small>
                        </td>
                        <td>
                            {{ $project->open_tasks_count }} open
                            <small>{{ $project->tasks_count }} total &middot; {{ $project->overdue_tasks_count }} overdue</small>
                        </td>
                        <td>
                            {{ $project->due_date?->format('M d, Y') ?? '-' }}
                            <small>{{ $project->due_date?->isPast() && ! in_array($project->status, ['completed', 'cancelled'], true) ? 'Past due' : ($project->due_date?->diffForHumans() ?? '') }}</small>
                        </td>
                        <td>@include('partials.status-badge', ['status' => $project->status])</td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('company-admin.projects.show', $project) }}" title="Open project"><i class="fa-solid fa-eye"></i></a>
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('company-admin.projects.edit', $project) }}" title="Edit project"><i class="fa-solid fa-pen"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="empty-cell">
                            @include('partials.empty-state', ['icon' => 'fa-diagram-project', 'title' => 'No projects found', 'message' => 'Try changing the search or filters.'])
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $projects->links() }}
</section>
@endsection
