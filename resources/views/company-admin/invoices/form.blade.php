@extends('layouts.app')

@section('title', ($invoice->exists ? 'Edit' : 'Create').' Invoice - Elevanix')

@section('content')
@php
    $existingItems = old('items', $invoice->items->map(fn ($item) => [
        'description' => $item->description,
        'quantity' => $item->quantity,
        'unit_price' => $item->unit_price,
    ])->all());

    if ($existingItems === []) {
        $existingItems = [['description' => '', 'quantity' => 1, 'unit_price' => 0]];
    }
@endphp
@include('partials.page-header', ['eyebrow' => 'Invoices', 'title' => $invoice->exists ? 'Edit Invoice' : 'Create Invoice'])

<form class="content-card" method="POST" action="{{ $invoice->exists ? route('company-admin.invoices.update', $invoice) : route('company-admin.invoices.store') }}" data-loading-form data-invoice-form>
    @csrf
    @if($invoice->exists)
        @method('PUT')
    @endif

    <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label">Invoice Number</label>
            <input class="form-control" name="invoice_number" value="{{ old('invoice_number', $invoice->invoice_number) }}" placeholder="Auto generated">
        </div>
        <div class="col-md-3">
            <label class="form-label">Client <span class="required-mark">*</span></label>
            <select class="form-select" name="client_id" required>
                <option value="">Choose client</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}" @selected(old('client_id', $invoice->client_id)==$client->id)>{{ $client->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Project</label>
            <select class="form-select" name="project_id">
                <option value="">Choose project</option>
                @foreach($projects as $project)
                    <option value="{{ $project->id }}" data-client-id="{{ $project->client_id }}" @selected(old('project_id', $invoice->project_id)==$project->id)>{{ $project->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Status</label>
            <select class="form-select" name="status">
                @foreach(['draft','sent','partially_paid','paid','overdue','cancelled'] as $status)
                    <option value="{{ $status }}" @selected(old('status', $invoice->status ?: 'draft')===$status)>{{ str_replace('_',' ',ucfirst($status)) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Issue Date</label>
            <input class="form-control" type="date" name="issue_date" value="{{ old('issue_date', optional($invoice->issue_date)->format('Y-m-d') ?: now()->format('Y-m-d')) }}" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Due Date</label>
            <input class="form-control" type="date" name="due_date" value="{{ old('due_date', optional($invoice->due_date)->format('Y-m-d')) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Tax Amount</label>
            <input class="form-control" type="number" step="0.01" min="0" name="tax" value="{{ old('tax', $invoice->tax ?? 0) }}" data-invoice-tax>
        </div>
        <div class="col-md-3">
            <label class="form-label">Paid Amount</label>
            <input class="form-control" type="number" step="0.01" min="0" name="paid_amount" value="{{ old('paid_amount', $invoice->paid_amount ?? 0) }}">
        </div>
    </div>

    <div class="mt-4">
        <div class="content-card-header mb-2">
            <div>
                <h2>Invoice Items</h2>
                <p>Line totals are calculated from quantity and unit price.</p>
            </div>
            <button class="btn btn-sm btn-outline-primary" type="button" data-add-invoice-item><i class="fa-solid fa-plus"></i>Add item</button>
        </div>
        <div class="table-responsive">
            <table class="table align-middle" data-invoice-items>
                <thead><tr><th>Description</th><th style="width: 140px;">Qty</th><th style="width: 180px;">Unit Price</th><th style="width: 160px;">Line Total</th><th class="text-end" style="width: 80px;">Action</th></tr></thead>
                <tbody>
                    @foreach($existingItems as $index => $item)
                        <tr>
                            <td><input class="form-control" name="items[{{ $index }}][description]" value="{{ $item['description'] ?? '' }}" required></td>
                            <td><input class="form-control" type="number" min="0.01" step="0.01" name="items[{{ $index }}][quantity]" value="{{ $item['quantity'] ?? 1 }}" data-line-quantity required></td>
                            <td><input class="form-control" type="number" min="0" step="0.01" name="items[{{ $index }}][unit_price]" value="{{ $item['unit_price'] ?? 0 }}" data-line-price required></td>
                            <td><strong data-line-total>{{ $currency }} 0.00</strong></td>
                            <td class="text-end"><button class="btn btn-sm btn-outline-danger" type="button" data-remove-invoice-item title="Remove item"><i class="fa-solid fa-trash"></i></button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <input type="hidden" name="subtotal" value="{{ old('subtotal', $invoice->subtotal ?? 0) }}" data-invoice-subtotal-input>
    <input type="hidden" name="total" value="{{ old('total', $invoice->total ?? 0) }}" data-invoice-total-input>

    <div class="row g-3 mt-2">
        <div class="col-md-8">
            <label class="form-label">Notes</label>
            <textarea class="form-control" name="notes" rows="4">{{ old('notes', $invoice->notes) }}</textarea>
        </div>
        <div class="col-md-4">
            <dl class="detail-list mt-4">
                <dt>Subtotal</dt><dd><span data-invoice-subtotal>{{ $currency }} 0.00</span></dd>
                <dt>Tax</dt><dd><span data-invoice-tax-display>{{ $currency }} 0.00</span></dd>
                <dt>Total</dt><dd><strong data-invoice-total>{{ $currency }} 0.00</strong></dd>
            </dl>
        </div>
    </div>

    <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i>Save invoice</button>
        <a class="btn btn-outline-primary" href="{{ route('company-admin.invoices.index') }}">Cancel</a>
    </div>
</form>

<template id="invoice-item-template">
    <tr>
        <td><input class="form-control" name="items[__INDEX__][description]" required></td>
        <td><input class="form-control" type="number" min="0.01" step="0.01" name="items[__INDEX__][quantity]" value="1" data-line-quantity required></td>
        <td><input class="form-control" type="number" min="0" step="0.01" name="items[__INDEX__][unit_price]" value="0" data-line-price required></td>
        <td><strong data-line-total>{{ $currency }} 0.00</strong></td>
        <td class="text-end"><button class="btn btn-sm btn-outline-danger" type="button" data-remove-invoice-item title="Remove item"><i class="fa-solid fa-trash"></i></button></td>
    </tr>
</template>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-invoice-form]');
    if (!form) return;

    const tbody = form.querySelector('[data-invoice-items] tbody');
    const formatter = new Intl.NumberFormat(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const currency = @json($currency);

    const money = (value) => `${currency} ${formatter.format(value)}`;
    const numericValue = (input) => Number.parseFloat(input?.value || '0') || 0;

    const recalculate = () => {
        let subtotal = 0;

        tbody.querySelectorAll('tr').forEach((row) => {
            const quantity = numericValue(row.querySelector('[data-line-quantity]'));
            const price = numericValue(row.querySelector('[data-line-price]'));
            const lineTotal = Math.round(quantity * price * 100) / 100;
            subtotal += lineTotal;
            row.querySelector('[data-line-total]').textContent = money(lineTotal);
        });

        const tax = Math.max(0, numericValue(form.querySelector('[data-invoice-tax]')));
        const total = Math.round((subtotal + tax) * 100) / 100;
        form.querySelector('[data-invoice-subtotal]').textContent = money(subtotal);
        form.querySelector('[data-invoice-tax-display]').textContent = money(tax);
        form.querySelector('[data-invoice-total]').textContent = money(total);
        form.querySelector('[data-invoice-subtotal-input]').value = subtotal.toFixed(2);
        form.querySelector('[data-invoice-total-input]').value = total.toFixed(2);
    };

    form.addEventListener('input', recalculate);
    form.querySelector('[data-add-invoice-item]').addEventListener('click', () => {
        const index = tbody.querySelectorAll('tr').length;
        const template = document.getElementById('invoice-item-template').innerHTML.replaceAll('__INDEX__', index);
        tbody.insertAdjacentHTML('beforeend', template);
        recalculate();
    });
    form.addEventListener('click', (event) => {
        const button = event.target.closest('[data-remove-invoice-item]');
        if (!button || tbody.querySelectorAll('tr').length === 1) return;
        button.closest('tr').remove();
        recalculate();
    });

    recalculate();
});
</script>
@endsection
