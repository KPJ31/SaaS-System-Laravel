@extends('layouts.app')

@section('title', ($payment->exists ? 'Edit' : 'Create').' Payment - Elevanix')

@section('content')
@include('partials.page-header', ['eyebrow' => 'Payments', 'title' => $payment->exists ? 'Edit Payment' : 'Create Payment Request'])
<form class="content-card" method="POST" action="{{ $payment->exists ? route('company-admin.payments.update', $payment) : route('company-admin.payments.store') }}" data-loading-form>
    @csrf @if($payment->exists) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-md-4"><label class="form-label">Client</label><select class="form-select" name="client_id"><option value="">Choose client</option>@foreach($clients as $client)<option value="{{ $client->id }}" @selected(old('client_id', $payment->client_id)==$client->id)>{{ $client->name }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label">Project</label><select class="form-select" name="project_id"><option value="">Choose project</option>@foreach($projects as $project)<option value="{{ $project->id }}" @selected(old('project_id', $payment->project_id)==$project->id)>{{ $project->name }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label">Reference</label><input class="form-control" name="transaction_reference" value="{{ old('transaction_reference', $payment->transaction_reference) }}"></div>
        <div class="col-md-4"><label class="form-label">Amount <span class="required-mark">*</span></label><input class="form-control" type="number" step="0.01" name="amount" value="{{ old('amount', $payment->amount) }}" required></div>
        <div class="col-md-4"><label class="form-label">Method</label><input class="form-control" name="method" value="{{ old('method', $payment->method ?: 'bank_transfer') }}" required></div>
        <div class="col-md-4"><label class="form-label">Status</label><select class="form-select" name="status">@foreach(['pending','requested','proof_submitted','partially_paid','paid','rejected','refunded','received'] as $status)<option value="{{ $status }}" @selected(old('status', $payment->status ?: 'requested')===$status)>{{ str_replace('_',' ',ucfirst($status)) }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label">Paid Date</label><input class="form-control" type="date" name="paid_at" value="{{ old('paid_at', optional($payment->paid_at)->format('Y-m-d')) }}"></div>
        <div class="col-md-12"><label class="form-label">Notes</label><textarea class="form-control" name="notes" rows="3">{{ old('notes', $payment->notes) }}</textarea></div>
    </div>
    <div class="mt-4 d-flex gap-2"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i>Save payment</button><a class="btn btn-outline-primary" href="{{ route('company-admin.payments.index') }}">Cancel</a></div>
</form>
@endsection
