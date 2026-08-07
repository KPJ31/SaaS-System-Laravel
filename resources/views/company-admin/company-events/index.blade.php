@extends('layouts.app')

@section('title', 'Company Events - Elevanix')

@section('content')
@include('partials.page-header', [
    'eyebrow' => 'Calendar',
    'title' => 'Company Events',
    'description' => 'Create, edit and cancel company-wide calendar events.',
    'actions' => new \Illuminate\Support\HtmlString('<a class="btn btn-outline-primary" href="'.route('company-admin.calendar.index').'"><i class="fa-solid fa-calendar-days"></i>Calendar</a><a class="btn btn-primary" href="'.route('company-admin.company-events.create').'"><i class="fa-solid fa-plus"></i>New Event</a>'),
])

<div class="stat-grid">
    @include('partials.stat-card', ['label' => 'Scheduled', 'value' => $summary['scheduled'], 'icon' => 'fa-calendar-check', 'tone' => 'green'])
    @include('partials.stat-card', ['label' => 'Upcoming', 'value' => $summary['upcoming'], 'icon' => 'fa-clock', 'tone' => 'blue'])
    @include('partials.stat-card', ['label' => 'Cancelled', 'value' => $summary['cancelled'], 'icon' => 'fa-ban', 'tone' => 'yellow'])
</div>

<section class="content-card mb-3">
    <form class="row g-2">
        <div class="col-md-4"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Search title, description or location"></div>
        <div class="col-md-3"><select class="form-select" name="event_type"><option value="">All types</option>@foreach($types as $type)<option value="{{ $type }}" @selected(request('event_type') === $type)>{{ str_replace('_', ' ', ucfirst($type)) }}</option>@endforeach</select></div>
        <div class="col-md-3"><select class="form-select" name="status"><option value="">All status</option>@foreach(['scheduled','cancelled'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
        <div class="col-md-2"><button class="btn btn-primary w-100"><i class="fa-solid fa-filter"></i>Filter</button></div>
    </form>
</section>

<section class="content-card">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Event</th><th>Type</th><th>Starts</th><th>Ends</th><th>Location</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                @forelse($events as $event)
                    <tr>
                        <td><strong>{{ $event->title }}</strong><small>{{ $event->creator?->name ?? 'Company Admin' }}</small></td>
                        <td>{{ str_replace('_', ' ', ucfirst($event->event_type)) }}</td>
                        <td>{{ $event->start_at?->format('Y-m-d H:i') }}</td>
                        <td>{{ $event->end_at?->format('Y-m-d H:i') ?? '-' }}</td>
                        <td>{{ $event->location ?? '-' }}</td>
                        <td>@include('partials.status-badge', ['status' => $event->status])</td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('company-admin.company-events.show', $event) }}"><i class="fa-solid fa-eye"></i>View</a>
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('company-admin.company-events.edit', $event) }}"><i class="fa-solid fa-pen"></i>Edit</a>
                            @if($event->status !== 'cancelled')
                                <form class="d-inline" method="POST" action="{{ route('company-admin.company-events.cancel', $event) }}" data-confirm="Cancel this company event?">@csrf<button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-ban"></i>Cancel</button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty-cell">@include('partials.empty-state', ['icon' => 'fa-calendar-check', 'title' => 'No company events', 'message' => 'Scheduled company events appear here.'])</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $events->links() }}
</section>
@endsection
