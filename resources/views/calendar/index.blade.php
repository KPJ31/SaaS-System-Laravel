@extends('layouts.app')

@section('title', 'Calendar - Elevanix')

@section('content')
@include('partials.page-header', [
    'eyebrow' => 'Planning',
    'title' => 'Calendar',
    'description' => 'View company events, project deadlines, tasks, leave and personal work reminders in one place.',
    'actions' => $manageEventsUrl ? new \Illuminate\Support\HtmlString('<a class="btn btn-outline-primary" href="'.$manageEventsUrl.'"><i class="fa-solid fa-calendar-check"></i>Manage Events</a>') : null,
])

<section
    class="calendar-shell"
    data-calendar-app
    data-events-endpoint="{{ $eventsEndpoint }}"
    data-initial-date="{{ request('focus', today()->toDateString()) }}"
>
    <div class="calendar-toolbar">
        <div class="calendar-nav">
            <button class="icon-btn" type="button" data-calendar-prev aria-label="Previous period"><i class="fa-solid fa-chevron-left"></i></button>
            <button class="btn btn-outline-primary" type="button" data-calendar-today>Today</button>
            <button class="icon-btn" type="button" data-calendar-next aria-label="Next period"><i class="fa-solid fa-chevron-right"></i></button>
            <strong data-calendar-title></strong>
        </div>
        <div class="calendar-view-switch" role="group" aria-label="Calendar view">
            <button class="btn btn-sm btn-primary" type="button" data-calendar-view="month">Month</button>
            <button class="btn btn-sm btn-outline-primary" type="button" data-calendar-view="week">Week</button>
            <button class="btn btn-sm btn-outline-primary" type="button" data-calendar-view="day">Day</button>
        </div>
    </div>

    <div class="calendar-filters">
        @foreach($visualMap as $type => $meta)
            <label><input type="checkbox" value="{{ $type }}" data-calendar-type checked> <span class="calendar-dot calendar-dot-{{ $meta['tone'] }}"></span>{{ $meta['label'] }}</label>
        @endforeach
        <select class="form-select" data-calendar-project aria-label="Filter by project">
            <option value="">All projects</option>
            @foreach($projects as $project)
                <option value="{{ $project->id }}">{{ $project->name }}</option>
            @endforeach
        </select>
        @if($employees->isNotEmpty())
            <select class="form-select" data-calendar-employee aria-label="Filter by employee">
                <option value="">All employees</option>
                @foreach($employees as $employee)
                    <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                @endforeach
            </select>
        @endif
    </div>

    <div class="calendar-status" data-calendar-status>Loading calendar...</div>
    <div class="calendar-grid" data-calendar-grid aria-live="polite"></div>
    <aside class="calendar-day-panel">
        <div class="content-card-header">
            <div>
                <h2 data-calendar-day-title>Day Schedule</h2>
                <p data-calendar-day-subtitle>No date selected.</p>
            </div>
        </div>
        <div class="calendar-event-list" data-calendar-day-list></div>
    </aside>
</section>
@endsection
