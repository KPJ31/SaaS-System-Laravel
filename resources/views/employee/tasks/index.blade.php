@extends('layouts.app')
@section('title', 'My Tasks - Elevanix')
@section('content')
@include('partials.page-header', ['eyebrow' => 'Employee', 'title' => 'My Tasks', 'description' => 'Search, filter and manage assigned task progress.'])
<section class="content-card mb-3">
    <form class="row g-2">
        <div class="col-md-3"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Search tasks"></div>
        <div class="col-md-3"><select class="form-select" name="project_id"><option value="">All projects</option>@foreach($projects as $project)<option value="{{ $project->id }}" @selected(request('project_id') == $project->id)>{{ $project->name }}</option>@endforeach</select></div>
        <div class="col-md-2"><select class="form-select" name="status"><option value="">All status</option>@foreach(['todo','assigned','in_progress','paused','blocked','submitted','under_review','completed','cancelled'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>@endforeach</select></div>
        <div class="col-md-2"><select class="form-select" name="priority"><option value="">All priority</option>@foreach(['low','medium','high','urgent'] as $priority)<option value="{{ $priority }}" @selected(request('priority') === $priority)>{{ ucfirst($priority) }}</option>@endforeach</select></div>
        <div class="col-md-2"><button class="btn btn-primary w-100"><i class="fa-solid fa-filter"></i>Filter</button></div>
    </form>
</section>
<section class="content-card">
    <div class="content-card-header"><h2>{{ $tasks->total() }} Tasks</h2></div>
    @include('employee.tasks._table', ['tasks' => $tasks])
    {{ $tasks->links() }}
</section>
@endsection
