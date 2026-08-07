@extends('layouts.app')

@section('title', 'Company Event - Elevanix')

@section('content')
@include('partials.page-header', [
    'eyebrow' => 'Company Event',
    'title' => $event->title,
    'description' => $event->description ?? 'Company-wide calendar event.',
    'actions' => new \Illuminate\Support\HtmlString('<a class="btn btn-outline-primary" href="'.route('company-admin.calendar.index', ['focus' => $event->start_at?->toDateString()]).'"><i class="fa-solid fa-calendar-days"></i>Calendar</a><a class="btn btn-primary" href="'.route('company-admin.company-events.edit', $event).'"><i class="fa-solid fa-pen"></i>Edit</a>'),
])

<div class="content-grid">
    <section class="content-card">
        <h2>Details</h2>
        <dl class="detail-list mt-3">
            <dt>Status</dt><dd>@include('partials.status-badge', ['status' => $event->status])</dd>
            <dt>Type</dt><dd>{{ str_replace('_', ' ', ucfirst($event->event_type)) }}</dd>
            <dt>Starts</dt><dd>{{ $event->start_at?->format('Y-m-d H:i') }}</dd>
            <dt>Ends</dt><dd>{{ $event->end_at?->format('Y-m-d H:i') ?? '-' }}</dd>
            <dt>Location</dt><dd>{{ $event->location ?? '-' }}</dd>
            <dt>Meeting Link</dt><dd>@if($event->meeting_link)<a href="{{ $event->meeting_link }}" target="_blank" rel="noopener">{{ $event->meeting_link }}</a>@else - @endif</dd>
            <dt>Created By</dt><dd>{{ $event->creator?->name ?? '-' }}</dd>
        </dl>
    </section>
    <section class="content-card">
        <h2>Actions</h2>
        @if($event->status !== 'cancelled')
            <form method="POST" action="{{ route('company-admin.company-events.cancel', $event) }}" data-confirm="Cancel this company event?" data-loading-form>
                @csrf
                <button class="btn btn-outline-danger mt-3" type="submit" data-loading-text="Cancelling..."><i class="fa-solid fa-ban"></i>Cancel Event</button>
            </form>
        @else
            @include('partials.empty-state', ['icon' => 'fa-ban', 'title' => 'Event cancelled', 'message' => 'Cancelled events remain visible for planning history.'])
        @endif
    </section>
</div>
@endsection
