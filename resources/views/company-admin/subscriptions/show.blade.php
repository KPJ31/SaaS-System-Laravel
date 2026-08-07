@extends('layouts.app')

@section('title', 'Plan Change Request - Elevanix')

@section('content')
@include('partials.page-header', ['eyebrow' => 'Plan Change', 'title' => 'Request #'.$changeRequest->id, 'description' => 'Track payment, review, and activation status.'])
<div class="row g-3">
    <div class="col-lg-7"><section class="content-card h-100"><h2>Request Details</h2><dl class="detail-list mt-3"><dt>Current Plan</dt><dd>{{ $changeRequest->currentPlan?->name }}</dd><dt>Requested Plan</dt><dd>{{ $changeRequest->requestedPlan?->name }}</dd><dt>Change Type</dt><dd>{{ str_replace('_', ' ', $changeRequest->change_type) }}</dd><dt>Billing Cycle</dt><dd>{{ ucfirst($changeRequest->billing_cycle) }}</dd><dt>Amount Due</dt><dd>${{ number_format($changeRequest->payable_amount, 2) }}</dd><dt>Status</dt><dd>@include('partials.status-badge', ['status' => $changeRequest->status])</dd><dt>Review Note</dt><dd>{{ $changeRequest->review_note ?? '-' }}</dd></dl></section></div>
    <div class="col-lg-5"><section class="content-card h-100"><h2>Payment</h2><dl class="detail-list mt-3"><dt>Status</dt><dd>{{ $changeRequest->payment?->status ? str_replace('_', ' ', $changeRequest->payment->status) : 'Not required/submitted' }}</dd><dt>Reference</dt><dd>{{ $changeRequest->payment?->transaction_reference ?? '-' }}</dd><dt>Proof</dt><dd>{{ $changeRequest->payment?->proof_path ? 'Uploaded' : '-' }}</dd></dl><div class="d-flex flex-wrap gap-2">@if($changeRequest->status === 'payment_required')<a class="btn btn-primary" href="{{ route('company-admin.subscription.change.payment', $changeRequest) }}"><i class="fa-solid fa-upload"></i>Submit Proof</a>@endif@if($changeRequest->canBeCancelled())<form method="POST" action="{{ route('company-admin.subscription.change.cancel', $changeRequest) }}" data-confirm="Cancel this request?">@csrf<button class="btn btn-outline-danger" type="submit">Cancel Request</button></form>@endif<a class="btn btn-outline-primary" href="{{ route('company-admin.subscription.index') }}">Back</a></div></section></div>
</div>
@endsection
