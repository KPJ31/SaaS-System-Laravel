@extends('layouts.app')

@section('title', 'Print '.$invoice->invoice_number.' - Elevanix')

@section('content')
<section class="content-card">
    <div class="d-flex justify-content-between align-items-start mb-4"><div><h1>{{ $invoice->company->name }}</h1><p>{{ $invoice->company->address }}</p></div><div class="text-end"><h2>{{ $invoice->invoice_number }}</h2>@include('partials.status-badge', ['status' => $invoice->status])</div></div>
    <dl class="detail-list"><dt>Bill To</dt><dd>{{ $invoice->client->name }}<br>{{ $invoice->client->email }}</dd><dt>Issue Date</dt><dd>{{ $invoice->issue_date?->format('Y-m-d') }}</dd><dt>Due Date</dt><dd>{{ $invoice->due_date?->format('Y-m-d') ?? '-' }}</dd><dt>Project</dt><dd>{{ $invoice->project?->name ?? '-' }}</dd></dl>
    <hr>
    <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Description</th><th>Qty</th><th>Unit Price</th><th class="text-end">Line Total</th></tr></thead><tbody>@forelse($invoice->items as $item)<tr><td>{{ $item->description }}</td><td>{{ number_format($item->quantity, 2) }}</td><td>{{ $currency }} {{ number_format($item->unit_price, 2) }}</td><td class="text-end">{{ $currency }} {{ number_format($item->line_total, 2) }}</td></tr>@empty<tr><td colspan="4">No line items recorded.</td></tr>@endforelse</tbody></table></div>
    <div class="text-end"><p>Subtotal: {{ $currency }} {{ number_format($invoice->subtotal, 2) }}</p><p>Tax: {{ $currency }} {{ number_format($invoice->tax, 2) }}</p><p>Paid: {{ $currency }} {{ number_format($invoice->paid_amount, 2) }}</p><h2>Balance: {{ $currency }} {{ number_format($invoice->balance_amount, 2) }}</h2><h2>Total: {{ $currency }} {{ number_format($invoice->total, 2) }}</h2></div>
    @if($paymentInstructions)
        <hr>
        <h2>Payment Instructions</h2>
        <p>{{ $paymentInstructions }}</p>
    @endif
</section>
@endsection
