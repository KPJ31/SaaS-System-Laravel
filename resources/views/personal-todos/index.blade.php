@extends('layouts.app')

@section('title', 'My Todos - Elevanix')

@section('content')
@include('partials.page-header', [
    'eyebrow' => 'Personal',
    'title' => 'My Todos',
    'description' => 'Private reminders and lightweight daily planning.',
])

<div class="stat-grid mb-3">
    @include('partials.stat-card', ['label' => 'Open', 'value' => $summary['open'], 'icon' => 'fa-list-check', 'tone' => 'blue'])
    @include('partials.stat-card', ['label' => 'Overdue', 'value' => $summary['overdue'], 'icon' => 'fa-triangle-exclamation', 'tone' => 'danger'])
    @include('partials.stat-card', ['label' => 'Pinned', 'value' => $summary['pinned'], 'icon' => 'fa-thumbtack', 'tone' => 'yellow'])
    @include('partials.stat-card', ['label' => 'Completed', 'value' => $summary['completed'], 'icon' => 'fa-circle-check', 'tone' => 'green'])
</div>

<div class="content-grid mb-3">
    <section class="content-card">
        <div class="content-card-header"><div><h2>Add Todo</h2><p>Keep it small and personal.</p></div></div>
        <form method="POST" action="{{ route($routePrefix.'.store') }}" data-loading-form>
            @csrf
            <div class="row g-3">
                <div class="col-12"><label class="form-label" for="title">Title <span class="required-mark">*</span></label><input class="form-control" id="title" name="title" value="{{ old('title') }}" required></div>
                <div class="col-md-6"><label class="form-label" for="priority">Priority</label><select class="form-select" id="priority" name="priority">@foreach(['low','medium','high','urgent'] as $priority)<option value="{{ $priority }}" @selected(old('priority', 'medium') === $priority)>{{ ucfirst($priority) }}</option>@endforeach</select></div>
                <div class="col-md-6"><label class="form-label" for="due_date">Due Date</label><input class="form-control" id="due_date" type="date" name="due_date" value="{{ old('due_date') }}"></div>
                <div class="col-12"><label class="form-label" for="notes">Notes</label><textarea class="form-control" id="notes" name="notes" rows="3">{{ old('notes') }}</textarea></div>
                <div class="col-12"><label class="form-check"><input class="form-check-input" type="checkbox" name="pinned" value="1" @checked(old('pinned'))><span class="form-check-label">Pin this todo</span></label></div>
                <div class="col-12"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-plus"></i>Add todo</button></div>
            </div>
        </form>
    </section>

    <section class="content-card">
        <div class="content-card-header"><div><h2>Filters</h2><p>Find today, late, or completed reminders.</p></div></div>
        <form class="row g-2" method="GET">
            <div class="col-12"><label class="form-label" for="search">Search</label><input class="form-control" id="search" name="search" value="{{ request('search') }}" placeholder="Search todos"></div>
            <div class="col-md-4"><label class="form-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">All</option>@foreach(['open','completed','dismissed'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label" for="filter_priority">Priority</label><select class="form-select" id="filter_priority" name="priority"><option value="">All</option>@foreach(['low','medium','high','urgent'] as $priority)<option value="{{ $priority }}" @selected(request('priority') === $priority)>{{ ucfirst($priority) }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label" for="due">Due</label><select class="form-select" id="due" name="due"><option value="">Any</option><option value="today" @selected(request('due') === 'today')>Today</option><option value="overdue" @selected(request('due') === 'overdue')>Overdue</option></select></div>
            <div class="col-12 d-flex flex-wrap gap-2"><button class="btn btn-outline-primary" type="submit"><i class="fa-solid fa-filter"></i>Filter</button><a class="btn btn-outline-primary" href="{{ route($routePrefix.'.index') }}">Clear</a></div>
        </form>
    </section>
</div>

<section class="content-card">
    <div class="content-card-header"><div><h2>{{ $todos->total() }} Todos</h2><p>Only you can see and manage these items.</p></div></div>
    <div class="todo-list">
        @forelse($todos as $todo)
            <article class="todo-item {{ $todo->status !== 'open' ? 'is-muted' : '' }}">
                <form method="POST" action="{{ route($routePrefix.'.complete', $todo) }}">
                    @csrf
                    <button class="icon-btn" type="submit" aria-label="Complete {{ $todo->title }}" @disabled($todo->status !== 'open')><i class="fa-regular fa-circle-check"></i></button>
                </form>
                <form method="POST" action="{{ route($routePrefix.'.update', $todo) }}" class="todo-edit" data-loading-form>
                    @csrf
                    @method('PATCH')
                    <div class="row g-2 align-items-start">
                        <div class="col-lg-4"><input class="form-control" name="title" value="{{ old('title', $todo->title) }}" required></div>
                        <div class="col-lg-3"><input class="form-control" name="notes" value="{{ old('notes', $todo->notes) }}" placeholder="Notes"></div>
                        <div class="col-lg-2"><select class="form-select" name="priority">@foreach(['low','medium','high','urgent'] as $priority)<option value="{{ $priority }}" @selected($todo->priority === $priority)>{{ ucfirst($priority) }}</option>@endforeach</select></div>
                        <div class="col-lg-2"><input class="form-control" type="date" name="due_date" value="{{ $todo->due_date?->format('Y-m-d') }}"></div>
                        <div class="col-lg-1"><label class="form-check"><input class="form-check-input" type="checkbox" name="pinned" value="1" @checked($todo->pinned)><span class="visually-hidden">Pinned</span></label></div>
                        <div class="col-12 d-flex flex-wrap align-items-center gap-2">
                            @include('partials.priority-badge', ['priority' => $todo->priority])
                            @include('partials.status-badge', ['status' => $todo->status])
                            @if($todo->isOverdue()) @include('partials.status-badge', ['status' => 'overdue']) @endif
                            @if($todo->due_date)<small>Due {{ $todo->due_date->format('M d, Y') }}</small>@endif
                            <button class="btn btn-sm btn-outline-primary" type="submit">Save</button>
                        </div>
                    </div>
                </form>
                <form method="POST" action="{{ route($routePrefix.'.destroy', $todo) }}" data-confirm="Dismiss this todo?">
                    @csrf
                    @method('DELETE')
                    <button class="icon-btn" type="submit" aria-label="Dismiss {{ $todo->title }}"><i class="fa-solid fa-xmark"></i></button>
                </form>
            </article>
        @empty
            @include('partials.empty-state', ['icon' => 'fa-list-check', 'title' => 'No todos', 'message' => 'Add a private reminder when something small needs a place to land.'])
        @endforelse
    </div>
    {{ $todos->links() }}
</section>
@endsection
