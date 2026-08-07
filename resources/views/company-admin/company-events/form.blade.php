@extends('layouts.app')

@section('title', ($event->exists ? 'Edit' : 'New').' Company Event - Elevanix')

@section('content')
@include('partials.page-header', [
    'eyebrow' => 'Calendar',
    'title' => $event->exists ? 'Edit Company Event' : 'New Company Event',
    'description' => 'Company events are visible to active company admins and employees.',
])

<section class="content-card">
    <form method="POST" action="{{ $event->exists ? route('company-admin.company-events.update', $event) : route('company-admin.company-events.store') }}" data-loading-form>
        @csrf
        @if($event->exists) @method('PUT') @endif
        <div class="row g-3">
            <div class="col-md-8"><label class="form-label">Title</label><input class="form-control" name="title" value="{{ old('title', $event->title) }}" required maxlength="255"></div>
            <div class="col-md-4"><label class="form-label">Type</label><select class="form-select" name="event_type" required>@foreach($types as $type)<option value="{{ $type }}" @selected(old('event_type', $event->event_type ?: 'meeting') === $type)>{{ str_replace('_', ' ', ucfirst($type)) }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Start</label><input class="form-control" type="datetime-local" name="start_at" value="{{ old('start_at', $event->start_at?->format('Y-m-d\TH:i')) }}" required></div>
            <div class="col-md-4"><label class="form-label">End</label><input class="form-control" type="datetime-local" name="end_at" value="{{ old('end_at', $event->end_at?->format('Y-m-d\TH:i')) }}"></div>
            <div class="col-md-4"><label class="form-label">Location</label><input class="form-control" name="location" value="{{ old('location', $event->location) }}" maxlength="255"></div>
            <div class="col-12"><label class="form-label">Meeting link</label><input class="form-control" type="url" name="meeting_link" value="{{ old('meeting_link', $event->meeting_link) }}" maxlength="255"></div>
            <div class="col-12"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="5">{{ old('description', $event->description) }}</textarea></div>
        </div>
        <div class="mt-3 d-flex gap-2">
            <button class="btn btn-primary" type="submit" data-loading-text="Saving Event..."><i class="fa-solid fa-floppy-disk"></i>Save Event</button>
            <a class="btn btn-outline-primary" href="{{ route('company-admin.company-events.index') }}">Back</a>
        </div>
    </form>
</section>
@endsection
