@extends('layouts.app')

@section('title', 'Leave Request - Elevanix')

@section('content')
@include('partials.page-header', ['eyebrow' => 'Leave Review', 'title' => $leave->user?->name.' - '.ucfirst($leave->leave_type), 'description' => $leave->reason])
<div class="content-grid">
    <section class="content-card">
        <h2>Request Details</h2>
        <dl class="detail-list mt-3"><dt>Employee</dt><dd>{{ $leave->user?->name }}</dd><dt>Status</dt><dd>@include('partials.status-badge', ['status' => $leave->status])</dd><dt>Dates</dt><dd>{{ $leave->start_date->format('Y-m-d') }} to {{ $leave->end_date->format('Y-m-d') }}</dd><dt>Total days</dt><dd>{{ $leave->total_days }}</dd><dt>Reason</dt><dd>{{ $leave->reason }}</dd><dt>Reviewer</dt><dd>{{ $leave->reviewer?->name ?? '-' }}</dd><dt>Reviewed at</dt><dd>{{ $leave->reviewed_at?->format('Y-m-d H:i') ?? '-' }}</dd><dt>Review note</dt><dd>{{ $leave->review_note ?? '-' }}</dd></dl>
    </section>
    <section class="content-card">
        <h2>Decision</h2>
        @if($leave->status === 'pending')
            <form method="POST" action="{{ route('company-admin.leave-requests.review', $leave) }}" class="mt-3" data-loading-form>
                @csrf @method('PATCH')
                <label class="form-label">Decision</label>
                <select class="form-select mb-2" name="status"><option value="approved">Approve</option><option value="rejected">Reject</option></select>
                <label class="form-label">Review note</label>
                <textarea class="form-control mb-2" name="review_note" rows="4">{{ old('review_note') }}</textarea>
                <button class="btn btn-primary"><i class="fa-solid fa-check"></i>Save Decision</button>
            </form>
        @else
            @include('partials.empty-state', ['icon' => 'fa-check-circle', 'title' => 'Request already reviewed', 'message' => 'Only pending leave requests can be approved or rejected.'])
        @endif
    </section>
</div>
@endsection
