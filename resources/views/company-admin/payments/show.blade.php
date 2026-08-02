@extends('layouts.app')

@section('title', 'Payment '.$payment->id.' - Elevanix')

@section('content')
@include('partials.page-header', ['eyebrow' => 'Payment Details', 'title' => $payment->transaction_reference ?? 'Payment #'.$payment->id, 'description' => $payment->client?->name])
<div class="content-grid">
    <section class="content-card"><h2>Payment</h2><dl class="detail-list mt-3"><dt>Status</dt><dd>@include('partials.status-badge', ['status' => $payment->status])</dd><dt>Amount</dt><dd>${{ number_format($payment->amount, 2) }}</dd><dt>Project</dt><dd>{{ $payment->project?->name ?? '-' }}</dd><dt>Method</dt><dd>{{ $payment->method }}</dd><dt>Paid Date</dt><dd>{{ $payment->paid_at?->format('Y-m-d') ?? '-' }}</dd><dt>Verification Note</dt><dd>{{ $payment->verification_note ?? '-' }}</dd></dl></section>
    <section class="content-card"><h2>Verification</h2><form method="POST" action="{{ route('company-admin.payments.verify', $payment) }}" data-confirm="Verify this payment?">@csrf<label class="form-label mt-3">Note</label><textarea class="form-control" name="verification_note" rows="3"></textarea><button class="btn btn-primary mt-3" type="submit">Verify payment</button></form><form class="mt-3" method="POST" action="{{ route('company-admin.payments.reject', $payment) }}" data-confirm="Reject this payment?">@csrf<label class="form-label">Reason</label><textarea class="form-control" name="verification_note" rows="3" required></textarea><button class="btn btn-outline-danger mt-2" type="submit">Reject payment</button></form></section>
</div>
@endsection
