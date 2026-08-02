@extends('layouts.app')

@section('title', ($invoice->exists ? 'Edit' : 'Create').' Invoice - Elevanix')

@section('content')
@include('partials.page-header', ['eyebrow' => 'Invoices', 'title' => $invoice->exists ? 'Edit Invoice' : 'Create Invoice'])
<form class="content-card" method="POST" action="{{ $invoice->exists ? route('company-admin.invoices.update', $invoice) : route('company-admin.invoices.store') }}" data-loading-form>
    @csrf @if($invoice->exists) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-md-4"><label class="form-label">Invoice Number</label><input class="form-control" name="invoice_number" value="{{ old('invoice_number', $invoice->invoice_number) }}" placeholder="Auto generated"></div>
        <div class="col-md-4"><label class="form-label">Client <span class="required-mark">*</span></label><select class="form-select" name="client_id" required><option value="">Choose client</option>@foreach($clients as $client)<option value="{{ $client->id }}" @selected(old('client_id', $invoice->client_id)==$client->id)>{{ $client->name }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label">Project</label><select class="form-select" name="project_id"><option value="">Choose project</option>@foreach($projects as $project)<option value="{{ $project->id }}" @selected(old('project_id', $invoice->project_id)==$project->id)>{{ $project->name }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label">Issue Date</label><input class="form-control" type="date" name="issue_date" value="{{ old('issue_date', optional($invoice->issue_date)->format('Y-m-d') ?: now()->format('Y-m-d')) }}" required></div>
        <div class="col-md-3"><label class="form-label">Due Date</label><input class="form-control" type="date" name="due_date" value="{{ old('due_date', optional($invoice->due_date)->format('Y-m-d')) }}"></div>
        <div class="col-md-2"><label class="form-label">Subtotal</label><input class="form-control" type="number" step="0.01" name="subtotal" value="{{ old('subtotal', $invoice->subtotal ?? 0) }}" required></div>
        <div class="col-md-2"><label class="form-label">Tax</label><input class="form-control" type="number" step="0.01" name="tax" value="{{ old('tax', $invoice->tax ?? 0) }}"></div>
        <div class="col-md-2"><label class="form-label">Total</label><input class="form-control" type="number" step="0.01" name="total" value="{{ old('total', $invoice->total ?? 0) }}" required></div>
        <div class="col-md-3"><label class="form-label">Paid Amount</label><input class="form-control" type="number" step="0.01" name="paid_amount" value="{{ old('paid_amount', $invoice->paid_amount ?? 0) }}"></div>
        <div class="col-md-3"><label class="form-label">Status</label><select class="form-select" name="status">@foreach(['draft','sent','partially_paid','paid','overdue','cancelled'] as $status)<option value="{{ $status }}" @selected(old('status', $invoice->status ?: 'draft')===$status)>{{ str_replace('_',' ',ucfirst($status)) }}</option>@endforeach</select></div>
        <div class="col-md-12"><label class="form-label">Notes</label><textarea class="form-control" name="notes" rows="3">{{ old('notes', $invoice->notes) }}</textarea></div>
    </div>
    <div class="mt-4 d-flex gap-2"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i>Save invoice</button><a class="btn btn-outline-primary" href="{{ route('company-admin.invoices.index') }}">Cancel</a></div>
</form>
@endsection
