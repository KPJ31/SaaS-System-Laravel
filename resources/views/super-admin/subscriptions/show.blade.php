@extends('layouts.app')

@section('title', 'Subscription Details - Elevanix')

@section('content')
@include('partials.page-header', ['eyebrow' => 'Company Subscriptions', 'title' => $subscription->company?->name.' Subscription', 'description' => 'Change plans, extend dates, cancel, suspend or reactivate this company subscription.'])

<div class="row g-3">
    <div class="col-lg-7">
        <section class="content-card">
            <h2>Update Subscription</h2>
            <form method="POST" action="{{ route('super-admin.subscriptions.update', $subscription) }}" class="row g-3 mt-1">
                @csrf @method('PUT')
                <div class="col-md-6"><label class="form-label">Plan</label><select class="form-select" name="subscription_plan_id">@foreach($plans as $plan)<option value="{{ $plan->id }}" @selected(old('subscription_plan_id', $subscription->subscription_plan_id) == $plan->id)>{{ $plan->name }}</option>@endforeach</select></div>
                <div class="col-md-6"><label class="form-label">Status</label><select class="form-select" name="status">@foreach(['trialing','active','expired','cancelled','suspended'] as $status)<option value="{{ $status }}" @selected(old('status', $subscription->status) === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
                <div class="col-md-6"><label class="form-label">Starts at</label><input class="form-control" type="date" name="starts_at" value="{{ old('starts_at', $subscription->starts_at?->format('Y-m-d')) }}"></div>
                <div class="col-md-6"><label class="form-label">Trial ends at</label><input class="form-control" type="date" name="trial_ends_at" value="{{ old('trial_ends_at', $subscription->trial_ends_at?->format('Y-m-d')) }}"></div>
                <div class="col-md-6"><label class="form-label">Renews at</label><input class="form-control" type="date" name="renews_at" value="{{ old('renews_at', $subscription->renews_at?->format('Y-m-d')) }}"></div>
                <div class="col-md-6"><label class="form-label">Ends at</label><input class="form-control" type="date" name="ends_at" value="{{ old('ends_at', $subscription->ends_at?->format('Y-m-d')) }}"></div>
                <div class="col-md-6"><label class="form-label">Monthly price</label><input class="form-control" name="monthly_price" value="{{ old('monthly_price', $subscription->monthly_price) }}"></div>
                <div class="col-12"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save Subscription</button></div>
            </form>
        </section>
    </div>
    <div class="col-lg-5">
        <section class="content-card mb-3"><h2>Quick Actions</h2><div class="d-flex flex-wrap gap-2 mt-3">@foreach(['active' => 'Reactivate', 'suspended' => 'Suspend', 'cancelled' => 'Cancel', 'expired' => 'Mark Expired'] as $status => $label)<form method="POST" action="{{ route('super-admin.subscriptions.status', [$subscription, $status]) }}" data-confirm="{{ $label }} this subscription?">@csrf<button class="btn btn-outline-primary" type="submit">{{ $label }}</button></form>@endforeach</div></section>
        <section class="content-card"><h2>Payments</h2><div class="activity-list mt-3">@forelse($subscription->payments as $payment)<div><strong>{{ $payment->transaction_reference ?? 'Payment #'.$payment->id }}</strong><span>${{ number_format($payment->amount, 2) }} - {{ $payment->status }}</span></div>@empty @include('partials.empty-state', ['icon' => 'fa-money-bill', 'title' => 'No payments', 'message' => 'Subscription payments will appear here.']) @endforelse</div></section>
    </div>
</div>
@endsection
