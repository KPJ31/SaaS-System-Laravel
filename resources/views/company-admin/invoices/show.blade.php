@extends('layouts.app')

@section('title', $invoice->invoice_number.' - Elevanix')

@section('content')
@include('partials.page-header', ['eyebrow' => 'Invoice Details', 'title' => $invoice->invoice_number, 'description' => $invoice->client?->name])
<div class="content-grid">
    <section class="content-card"><h2>Invoice</h2><dl class="detail-list mt-3"><dt>Status</dt><dd>@include('partials.status-badge', ['status' => $invoice->status])</dd><dt>Total</dt><dd>${{ number_format($invoice->total, 2) }}</dd><dt>Paid</dt><dd>${{ number_format($invoice->paid_amount ?? 0, 2) }}</dd><dt>Balance</dt><dd>${{ number_format($invoice->balance_amount ?? 0, 2) }}</dd><dt>Issue Date</dt><dd>{{ $invoice->issue_date?->format('Y-m-d') }}</dd><dt>Due Date</dt><dd>{{ $invoice->due_date?->format('Y-m-d') ?? '-' }}</dd></dl></section>
    <section class="content-card"><h2>Actions</h2><div class="d-flex flex-wrap gap-2 mt-3"><a class="btn btn-outline-primary" href="{{ route('company-admin.invoices.print', $invoice) }}"><i class="fa-solid fa-print"></i>Print</a><form method="POST" action="{{ route('company-admin.invoices.send', $invoice) }}">@csrf<button class="btn btn-outline-primary" type="submit">Mark sent</button></form><form method="POST" action="{{ route('company-admin.invoices.paid', $invoice) }}" data-confirm="Mark this invoice as paid?">@csrf<button class="btn btn-primary" type="submit">Mark paid</button></form></div></section>
</div>
@endsection
