@extends('layouts.app')

@section('title', 'My Tasks - Elevanix')

@section('content')
@include('partials.page-header', [
    'eyebrow' => 'Employee',
    'title' => 'My Tasks',
    'description' => 'Search, filter and manage assigned task progress.',
])

<div class="stat-grid mb-3">
    @include('partials.stat-card', ['label' => 'Todo', 'value' => $summary['todo'], 'icon' => 'fa-clipboard-list', 'tone' => 'blue'])
    @include('partials.stat-card', ['label' => 'In Progress', 'value' => $summary['in_progress'], 'icon' => 'fa-spinner', 'tone' => 'green'])
    @include('partials.stat-card', ['label' => 'In Review', 'value' => $summary['review'], 'icon' => 'fa-paper-plane', 'tone' => 'yellow'])
    @include('partials.stat-card', ['label' => 'Overdue', 'value' => $summary['overdue'], 'icon' => 'fa-triangle-exclamation', 'tone' => 'danger'])
</div>

<section class="content-card mb-3">
    <form class="row g-2" method="GET">
        <div class="col-lg-3 col-md-6"><label class="form-label" for="search">Search</label><input class="form-control" id="search" name="search" value="{{ request('search') }}" placeholder="Search tasks"></div>
        <div class="col-lg-3 col-md-6"><label class="form-label" for="project_id">Project</label><select class="form-select" id="project_id" name="project_id"><option value="">All projects</option>@foreach($projects as $project)<option value="{{ $project->id }}" @selected((int) request('project_id') === $project->id)>{{ $project->name }}</option>@endforeach</select></div>
        <div class="col-lg-2 col-md-6"><label class="form-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">All statuses</option>@foreach($statuses as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>@endforeach</select></div>
        <div class="col-lg-2 col-md-6"><label class="form-label" for="priority">Priority</label><select class="form-select" id="priority" name="priority"><option value="">All priorities</option>@foreach($priorities as $priority)<option value="{{ $priority }}" @selected(request('priority') === $priority)>{{ ucfirst($priority) }}</option>@endforeach</select></div>
        <div class="col-lg-2 col-md-6"><label class="form-label" for="due">Due</label><select class="form-select" id="due" name="due"><option value="">Any due date</option><option value="today" @selected(request('due') === 'today')>Today</option><option value="overdue" @selected(request('due') === 'overdue')>Overdue</option><option value="upcoming" @selected(request('due') === 'upcoming')>Upcoming</option></select></div>
        <div class="col-12 d-flex flex-wrap gap-2"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter"></i>Filter</button><a class="btn btn-outline-primary" href="{{ route('employee.tasks.index') }}">Clear</a></div>
    </form>
</section>

<section class="content-card">
    <div class="content-card-header"><div><h2>{{ $tasks->total() }} Tasks</h2><p>Your assigned work queue.</p></div></div>
    @include('employee.tasks._table', ['tasks' => $tasks])
    {{ $tasks->links() }}
</section>
@endsection
