@extends('layouts.app')

@section('title', $projectRequest->title.' - Elevanix')

@section('content')
@include('partials.page-header', [
    'breadcrumbs' => [
        ['label' => 'Dashboard', 'url' => route('company-admin.dashboard')],
        ['label' => 'Project Requests', 'url' => route('company-admin.project-requests.index')],
        ['label' => $projectRequest->title],
    ],
    'eyebrow' => 'Project Request',
    'title' => $projectRequest->title,
    'description' => ($projectRequest->client?->name ?? 'No client').' | Submitted '.$projectRequest->created_at->format('Y-m-d'),
])

<div class="stat-grid mb-3">
    @include('partials.stat-card', ['label' => 'Status', 'value' => str_replace('_', ' ', ucfirst($projectRequest->status)), 'icon' => 'fa-circle-info', 'tone' => 'blue'])
    @include('partials.stat-card', ['label' => 'Estimated Budget', 'value' => number_format($projectRequest->estimated_budget ?? 0, 2), 'icon' => 'fa-sack-dollar', 'tone' => 'green'])
    @include('partials.stat-card', ['label' => 'Expected Start', 'value' => $projectRequest->expected_start_date?->format('Y-m-d') ?? '-', 'icon' => 'fa-calendar-day', 'tone' => 'primary'])
    @include('partials.stat-card', ['label' => 'Expected End', 'value' => $projectRequest->expected_end_date?->format('Y-m-d') ?? '-', 'icon' => 'fa-flag-checkered', 'tone' => 'yellow'])
</div>

<div class="content-grid mb-3">
    <section class="content-card">
        <div class="content-card-header"><div><h2>Request Overview</h2><p>Requested work and review context.</p></div></div>
        <dl class="detail-list mt-3">
            <dt>Status</dt><dd>@include('partials.status-badge', ['status' => $projectRequest->status])</dd>
            <dt>Service Type</dt><dd>{{ $projectRequest->service_type ?? '-' }}</dd>
            <dt>Created By</dt><dd>{{ $projectRequest->creator?->name ?? 'System' }}</dd>
            <dt>Approved By</dt><dd>{{ $projectRequest->approver?->name ?? '-' }}</dd>
            <dt>Approved At</dt><dd>{{ $projectRequest->approved_at?->format('Y-m-d H:i') ?? '-' }}</dd>
            <dt>Converted Project</dt><dd>@if($projectRequest->convertedProject)<a href="{{ route('company-admin.projects.show', $projectRequest->convertedProject) }}">{{ $projectRequest->convertedProject->name }}</a>@else - @endif</dd>
        </dl>
    </section>

    <section class="content-card">
        <div class="content-card-header"><div><h2>Client Information</h2><p>Client connected to this request.</p></div></div>
        <dl class="detail-list mt-3">
            <dt>Client</dt><dd>{{ $projectRequest->client?->name ?? '-' }}</dd>
            <dt>Organization</dt><dd>{{ $projectRequest->client?->company_name ?? '-' }}</dd>
            <dt>Email</dt><dd>{{ $projectRequest->client?->email ?? '-' }}</dd>
            <dt>Phone</dt><dd>{{ $projectRequest->client?->phone ?? '-' }}</dd>
        </dl>
    </section>
</div>

<div class="content-grid mb-3">
    <section class="content-card">
        <div class="content-card-header"><div><h2>Requirements</h2><p>Original requested scope and internal review notes.</p></div></div>
        <dl class="detail-list mt-3">
            <dt>Description</dt><dd>{{ $projectRequest->description ?? '-' }}</dd>
            <dt>Admin Note</dt><dd>{{ $projectRequest->admin_note ?? '-' }}</dd>
            <dt>Rejection Reason</dt><dd>{{ $projectRequest->rejection_reason ?? '-' }}</dd>
        </dl>
    </section>

    <section class="content-card">
        <div class="content-card-header"><div><h2>Review Actions</h2><p>Move the request through the existing review workflow.</p></div></div>

        @if($validStatuses !== [])
            <form method="POST" action="{{ route('company-admin.project-requests.update', $projectRequest) }}" data-loading-form>
                @csrf
                @method('PUT')
                <label class="form-label mt-3" for="status">Next Status</label>
                <select class="form-select" id="status" name="status">
                    @foreach($validStatuses as $status)
                        <option value="{{ $status }}">{{ str_replace('_', ' ', ucfirst($status)) }}</option>
                    @endforeach
                </select>
                <label class="form-label mt-3" for="admin_note">Admin Note</label>
                <textarea class="form-control" id="admin_note" name="admin_note" rows="4">{{ old('admin_note', $projectRequest->admin_note) }}</textarea>
                <button class="btn btn-primary mt-3" type="submit" data-loading-text="Saving review..."><i class="fa-solid fa-floppy-disk"></i>Save review</button>
            </form>
        @else
            @include('partials.empty-state', ['icon' => 'fa-lock', 'title' => 'No review actions available', 'message' => 'This request is currently read-only in the review workflow.'])
        @endif

        <div class="d-flex flex-wrap gap-2 mt-3">
            @if(in_array($projectRequest->status, ['pending', 'under_review'], true))
                <form method="POST" action="{{ route('company-admin.project-requests.approve', $projectRequest) }}" data-confirm="Approve this request?">@csrf<button class="btn btn-outline-primary" type="submit"><i class="fa-solid fa-circle-check"></i>Approve</button></form>
            @endif
            @if(in_array($projectRequest->status, ['approved', 'payment_confirmed'], true) && ! $projectRequest->converted_project_id)
                <form method="POST" action="{{ route('company-admin.project-requests.convert', $projectRequest) }}" data-confirm="Convert this request into a project?">@csrf<button class="btn btn-outline-primary" type="submit"><i class="fa-solid fa-diagram-project"></i>Convert</button></form>
            @endif
        </div>

        @if(in_array($projectRequest->status, ['pending', 'under_review'], true))
            <form class="mt-3" method="POST" action="{{ route('company-admin.project-requests.reject', $projectRequest) }}" data-confirm="Reject this request?">
                @csrf
                <label class="form-label" for="rejection_reason">Rejection Reason</label>
                <textarea class="form-control @error('rejection_reason') is-invalid @enderror" id="rejection_reason" name="rejection_reason" rows="3" required>{{ old('rejection_reason') }}</textarea>
                @error('rejection_reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <button class="btn btn-outline-danger mt-2" type="submit"><i class="fa-solid fa-ban"></i>Reject request</button>
            </form>
        @endif
    </section>
</div>

<section class="content-card">
    <div class="content-card-header"><div><h2>Review History</h2><p>Audit records for this project request.</p></div></div>
    <div class="activity-list">
        @forelse($activityLogs as $activity)
            <div><strong>{{ str((string) $activity->action)->replace('_', ' ')->headline() }}</strong><span>{{ $activity->description }} &middot; {{ $activity->user?->name ?? 'System' }} &middot; {{ $activity->created_at->diffForHumans() }}</span></div>
        @empty
            @include('partials.empty-state', ['icon' => 'fa-clipboard-list', 'title' => 'No review history', 'message' => 'Approval, rejection and conversion activity will appear here.'])
        @endforelse
    </div>
</section>
@endsection
