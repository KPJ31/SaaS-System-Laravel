@extends('layouts.app')

@section('title', 'Print '.$invoice->invoice_number.' - Elevanix')

@section('content')
<section class="content-card">
    <div class="d-flex justify-content-between align-items-start mb-4"><div><h1>{{ $invoice->company->name }}</h1><p>{{ $invoice->company->address }}</p></div><div class="text-end"><h2>{{ $invoice->invoice_number }}</h2>@include('partials.status-badge', ['status' => $invoice->status])</div></div>
    <dl class="detail-list"><dt>Bill To</dt><dd>{{ $invoice->client->name }}<br>{{ $invoice->client->email }}</dd><dt>Issue Date</dt><dd>{{ $invoice->issue_date?->format('Y-m-d') }}</dd><dt>Due Date</dt><dd>{{ $invoice->due_date?->format('Y-m-d') ?? '-' }}</dd><dt>Project</dt><dd>{{ $invoice->project?->name ?? '-' }}</dd></dl>
    <hr>
    <div class="text-end"><p>Subtotal: ${{ number_format($invoice->subtotal, 2) }}</p><p>Tax: ${{ number_format($invoice->tax, 2) }}</p><h2>Total: ${{ number_format($invoice->total, 2) }}</h2></div>
</section>
@endsection
