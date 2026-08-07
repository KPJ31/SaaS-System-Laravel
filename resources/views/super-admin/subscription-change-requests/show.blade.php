@extends('layouts.app')

@section('title', 'Review Plan Change - Elevanix')

@section('content')
@include('partials.page-header', ['eyebrow' => 'Plan Change Review', 'title' => 'Request #'.$changeRequest->id, 'description' => 'Approve only after usage and payment checks pass.'])
<div class="row g-3">
    <div class="col-lg-7">
        <section class="content-card h-100">
            <h2>Request Details</h2>
            <dl class="detail-list mt-3"><dt>Company</dt><dd>{{ $changeRequest->company?->name }}</dd><dt>Current Plan</dt><dd>{{ $changeRequest->currentPlan?->name }}</dd><dt>Requested Plan</dt><dd>{{ $changeRequest->requestedPlan?->name }}</dd><dt>Change Type</dt><dd>{{ str_replace('_', ' ', $changeRequest->change_type) }}</dd><dt>Billing Cycle</dt><dd>{{ ucfirst($changeRequest->billing_cycle) }}</dd><dt>Current Price</dt><dd>${{ number_format($changeRequest->current_price, 2) }}</dd><dt>Requested Price</dt><dd>${{ number_format($changeRequest->requested_price, 2) }}</dd><dt>Payable Amount</dt><dd>${{ number_format($changeRequest->payable_amount, 2) }}</dd><dt>Status</dt><dd>@include('partials.status-badge', ['status' => $changeRequest->status])</dd><dt>Request Note</dt><dd>{{ $changeRequest->request_note ?? '-' }}</dd></dl>
        </section>
    </div>
    <div class="col-lg-5">
        <section class="content-card h-100">
            <h2>Payment and Usage</h2>
            <dl class="detail-list mt-3"><dt>Payment Status</dt><dd>{{ $changeRequest->payment?->status ? str_replace('_', ' ', $changeRequest->payment->status) : 'Not submitted' }}</dd><dt>Reference</dt><dd>{{ $changeRequest->payment?->transaction_reference ?? '-' }}</dd><dt>Proof</dt><dd>{{ $changeRequest->payment?->proof_path ? 'Uploaded' : '-' }}</dd><dt>Employees</dt><dd>{{ $usage['employees'] }} / {{ $changeRequest->requestedPlan?->employee_limit }}</dd><dt>Projects</dt><dd>{{ $usage['projects'] }} / {{ $changeRequest->requestedPlan?->project_limit }}</dd><dt>Storage</dt><dd>{{ $usage['storage_mb'] }} MB / {{ $changeRequest->requestedPlan?->storage_limit_mb }} MB</dd></dl>
            @if($changeRequest->payment)
                <a class="btn btn-outline-primary mb-3" href="{{ route('super-admin.payments.show', $changeRequest->payment) }}"><i class="fa-solid fa-money-check-dollar"></i>Review Payment</a>
            @endif
        </section>
    </div>
</div>
<div class="row g-3 mt-1">
    <div class="col-lg-6"><section class="content-card h-100"><h2>Approve</h2><form method="POST" action="{{ route('super-admin.subscription-change-requests.approve', $changeRequest) }}" data-confirm="Approve and activate this plan change?">@csrf<label class="form-label" for="approve_note">Approval note</label><textarea class="form-control mb-3" id="approve_note" name="review_note" rows="3"></textarea><button class="btn btn-primary" type="submit"><i class="fa-solid fa-circle-check"></i>Approve and Activate</button></form></section></div>
    <div class="col-lg-6"><section class="content-card h-100"><h2>Reject</h2><form method="POST" action="{{ route('super-admin.subscription-change-requests.reject', $changeRequest) }}" data-confirm="Reject this plan change?">@csrf<label class="form-label" for="reject_note">Rejection reason</label><textarea class="form-control mb-3" id="reject_note" name="review_note" rows="3" required></textarea><button class="btn btn-outline-danger" type="submit"><i class="fa-solid fa-circle-xmark"></i>Reject Request</button></form></section></div>
</div>
@endsection
