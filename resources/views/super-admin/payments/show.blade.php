@extends('layouts.app')

@section('title', 'Payment Details - Elevanix')

@section('content')
@include('partials.page-header', ['eyebrow' => 'Payments', 'title' => $payment->transaction_reference ?? 'Payment #'.$payment->id, 'description' => 'Verify or reject submitted SaaS subscription payment records.'])
<div class="row g-3"><div class="col-lg-6"><section class="content-card"><h2>Payment Information</h2><dl class="detail-list mt-3"><dt>Company</dt><dd>{{ $payment->company?->name }}</dd><dt>Plan</dt><dd>{{ $payment->subscriptionPlan?->name ?? '-' }}</dd><dt>Amount</dt><dd>${{ number_format($payment->amount, 2) }}</dd><dt>Method</dt><dd>{{ str_replace('_', ' ', $payment->method) }}</dd><dt>Status</dt><dd>@include('partials.status-badge', ['status' => $payment->status])</dd><dt>Proof</dt><dd>{{ $payment->proof_path ? 'Uploaded' : 'No proof uploaded' }}</dd><dt>Note</dt><dd>{{ $payment->verification_note ?? $payment->notes ?? '-' }}</dd></dl></section></div><div class="col-lg-6"><section class="content-card"><h2>Verification</h2>@foreach(['verified' => 'Verify Payment', 'rejected' => 'Reject Payment', 'failed' => 'Mark Failed', 'refunded' => 'Refunded'] as $status => $label)<form method="POST" action="{{ route('super-admin.payments.status', [$payment, $status]) }}" class="mb-3" data-confirm="{{ $label }}?">@csrf<label class="form-label">{{ $label }} note</label><textarea class="form-control mb-2" name="verification_note" rows="2"></textarea><button class="btn btn-outline-primary" type="submit">{{ $label }}</button></form>@endforeach</section></div></div>
@endsection
