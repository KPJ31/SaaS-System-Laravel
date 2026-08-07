@extends('layouts.app')

@section('title', 'Submit Payment Proof - Elevanix')

@section('content')
@include('partials.page-header', ['eyebrow' => 'Subscription Payment', 'title' => 'Submit Payment Proof', 'description' => 'Upload proof for the requested plan amount.'])
<section class="content-card">
    <div class="row g-3">
        <div class="col-lg-5"><h2>Payment Summary</h2><dl class="detail-list mt-3"><dt>Requested Plan</dt><dd>{{ $changeRequest->requestedPlan?->name }}</dd><dt>Amount Due</dt><dd>${{ number_format($changeRequest->payable_amount, 2) }}</dd><dt>Status</dt><dd>@include('partials.status-badge', ['status' => $changeRequest->status])</dd></dl><div class="alert alert-info">Use your normal platform subscription payment instructions, then upload JPG, PNG, or PDF proof up to 5 MB.</div></div>
        <div class="col-lg-7"><form method="POST" action="{{ route('company-admin.subscription.change.payment.store', $changeRequest) }}" enctype="multipart/form-data">@csrf<div class="row g-3"><div class="col-md-6"><label class="form-label" for="amount">Paid Amount</label><input class="form-control" id="amount" name="amount" type="number" min="{{ $changeRequest->payable_amount }}" step="0.01" value="{{ old('amount', $changeRequest->payable_amount) }}"></div><div class="col-md-6"><label class="form-label" for="method">Payment Method</label><input class="form-control" id="method" name="method" value="{{ old('method', 'bank_transfer') }}"></div><div class="col-md-6"><label class="form-label" for="transaction_reference">Reference</label><input class="form-control" id="transaction_reference" name="transaction_reference" value="{{ old('transaction_reference') }}"></div><div class="col-md-6"><label class="form-label" for="paid_at">Payment Date</label><input class="form-control" id="paid_at" name="paid_at" type="date" value="{{ old('paid_at', now()->format('Y-m-d')) }}"></div><div class="col-12"><label class="form-label" for="proof">Payment Proof</label><input class="form-control" id="proof" name="proof" type="file" accept=".jpg,.jpeg,.png,.pdf"></div><div class="col-12"><label class="form-label" for="notes">Note</label><textarea class="form-control" id="notes" name="notes" rows="3">{{ old('notes') }}</textarea></div><div class="col-12"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-upload"></i>Submit Proof</button></div></div></form></div>
    </div>
</section>
@endsection
