@extends('layouts.app')

@section('title', $invoice->invoice_number.' - Elevanix')

@section('content')
@include('partials.page-header', ['eyebrow' => 'Invoice Details', 'title' => $invoice->invoice_number, 'description' => $invoice->client?->name])
<div class="content-grid mb-3">
    <section class="content-card"><h2>Invoice</h2><dl class="detail-list mt-3"><dt>Status</dt><dd>@include('partials.status-badge', ['status' => $invoice->status])</dd><dt>Client</dt><dd>{{ $invoice->client?->name }}</dd><dt>Project</dt><dd>{{ $invoice->project?->name ?? '-' }}</dd><dt>Total</dt><dd>{{ $currency }} {{ number_format($invoice->total, 2) }}</dd><dt>Paid</dt><dd>{{ $currency }} {{ number_format($invoice->paid_amount ?? 0, 2) }}</dd><dt>Balance</dt><dd>{{ $currency }} {{ number_format($invoice->balance_amount ?? 0, 2) }}</dd><dt>Issue Date</dt><dd>{{ $invoice->issue_date?->format('Y-m-d') }}</dd><dt>Due Date</dt><dd>{{ $invoice->due_date?->format('Y-m-d') ?? '-' }}</dd></dl></section>
    <section class="content-card"><h2>Actions</h2><div class="d-flex flex-wrap gap-2 mt-3"><a class="btn btn-outline-primary" href="{{ route('company-admin.invoices.print', $invoice) }}"><i class="fa-solid fa-print"></i>Print</a>@if($invoice->status === 'draft')<a class="btn btn-outline-primary" href="{{ route('company-admin.invoices.edit', $invoice) }}"><i class="fa-solid fa-pen"></i>Edit</a><form method="POST" action="{{ route('company-admin.invoices.send', $invoice) }}">@csrf<button class="btn btn-outline-primary" type="submit">Mark sent</button></form>@endif@if(in_array($invoice->status, ['sent','partially_paid','overdue'], true) && (float) $invoice->balance_amount > 0)<a class="btn btn-outline-primary" href="{{ route('company-admin.payments.create', ['invoice_id' => $invoice->id]) }}"><i class="fa-solid fa-money-check-dollar"></i>Add payment</a><form method="POST" action="{{ route('company-admin.invoices.paid', $invoice) }}" data-confirm="Mark this invoice as paid?">@csrf<button class="btn btn-primary" type="submit">Mark paid</button></form>@endif</div></section>
</div>

<div class="content-grid">
    <section class="content-card">
        <div class="content-card-header"><div><h2>Items</h2><p>{{ $invoice->items->count() }} line item{{ $invoice->items->count() === 1 ? '' : 's' }}.</p></div></div>
        <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Description</th><th>Qty</th><th>Unit Price</th><th class="text-end">Line Total</th></tr></thead><tbody>@forelse($invoice->items as $item)<tr><td>{{ $item->description }}</td><td>{{ number_format($item->quantity, 2) }}</td><td>{{ $currency }} {{ number_format($item->unit_price, 2) }}</td><td class="text-end">{{ $currency }} {{ number_format($item->line_total, 2) }}</td></tr>@empty<tr><td colspan="4" class="empty-cell">No line items recorded.</td></tr>@endforelse</tbody></table></div>
        <dl class="detail-list mt-3"><dt>Subtotal</dt><dd>{{ $currency }} {{ number_format($invoice->subtotal, 2) }}</dd><dt>Tax</dt><dd>{{ $currency }} {{ number_format($invoice->tax, 2) }}</dd><dt>Total</dt><dd><strong>{{ $currency }} {{ number_format($invoice->total, 2) }}</strong></dd></dl>
    </section>
    <section class="content-card">
        <div class="content-card-header"><div><h2>Payments</h2><p>Verified payments update this invoice balance.</p></div></div>
        <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Reference</th><th>Amount</th><th>Status</th><th>Verified By</th></tr></thead><tbody>@forelse($invoice->payments as $payment)<tr><td><a href="{{ route('company-admin.payments.show', $payment) }}">{{ $payment->transaction_reference ?? 'Payment #'.$payment->id }}</a><small>{{ $payment->paid_at?->format('Y-m-d') ?? $payment->created_at->format('Y-m-d') }}</small></td><td>{{ $currency }} {{ number_format($payment->amount, 2) }}</td><td>@include('partials.status-badge', ['status' => $payment->status])</td><td>{{ $payment->verifier?->name ?? '-' }}</td></tr>@empty<tr><td colspan="4" class="empty-cell">No payments linked.</td></tr>@endforelse</tbody></table></div>
    </section>
</div>
@endsection
