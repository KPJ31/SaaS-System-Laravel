@extends('layouts.app')

@section('title', 'Confirm Plan Change - Elevanix')

@section('content')
@include('partials.page-header', ['eyebrow' => 'Change Plan', 'title' => 'Confirm Plan Change', 'description' => 'Review the server-calculated plan change before submitting it for Super Admin approval.'])

<form method="POST" action="{{ route('company-admin.subscription.change.store') }}">
    @csrf
    <input type="hidden" name="requested_plan_id" value="{{ $requestedPlan->id }}">
    <div class="row g-3">
        <div class="col-lg-7">
            <section class="content-card h-100">
                <div class="content-card-header"><div><h2>{{ $currentPlan->name }} to {{ $requestedPlan->name }}</h2><p>{{ str_replace('_', ' ', ucfirst($summary['change_type'])) }} request.</p></div></div>
                <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Limit</th><th>Current</th><th>Requested</th></tr></thead><tbody>
                    <tr><td>Employees</td><td>{{ $currentPlan->employee_limit }}</td><td>{{ $requestedPlan->employee_limit }}</td></tr>
                    <tr><td>Projects</td><td>{{ $currentPlan->project_limit }}</td><td>{{ $requestedPlan->project_limit }}</td></tr>
                    <tr><td>Storage</td><td>{{ $currentPlan->storage_limit_mb }} MB</td><td>{{ $requestedPlan->storage_limit_mb }} MB</td></tr>
                    <tr><td>Monthly Price</td><td>${{ number_format($currentPlan->monthly_price, 2) }}</td><td>${{ number_format($requestedPlan->monthly_price, 2) }}</td></tr>
                    <tr><td>Yearly Price</td><td>${{ number_format($currentPlan->annual_price ?? $currentPlan->monthly_price * 12, 2) }}</td><td>${{ number_format($requestedPlan->annual_price ?? $requestedPlan->monthly_price * 12, 2) }}</td></tr>
                </tbody></table></div>
                @if($hasBlockingUsage)
                    <div class="alert alert-warning mt-3">Your current usage exceeds the selected plan limits. Reduce your usage or contact the Super Admin before requesting this downgrade.</div>
                @elseif($summary['change_type'] === 'downgrade')
                    <div class="alert alert-warning mt-3">Downgrades preserve existing data and normally take effect at renewal. New records may be restricted after activation.</div>
                @endif
            </section>
        </div>
        <div class="col-lg-5">
            <section class="content-card h-100">
                <h2>Request Summary</h2>
                <dl class="detail-list mt-3">
                    <dt>Billing Cycle</dt><dd><select class="form-select" name="billing_cycle"><option value="monthly" @selected(request('billing_cycle', 'monthly') === 'monthly')>Monthly</option>@if($requestedPlan->annual_price !== null)<option value="yearly" @selected(request('billing_cycle') === 'yearly')>Yearly</option>@endif</select></dd>
                    <dt>Current Price</dt><dd>${{ number_format($summary['current_price'], 2) }}</dd>
                    <dt>Requested Price</dt><dd>${{ number_format($summary['requested_price'], 2) }}</dd>
                    <dt>Amount Due</dt><dd>${{ number_format($summary['payable_amount'], 2) }}</dd>
                    <dt>Effective Date</dt><dd>{{ $summary['effective_date']?->format('M d, Y') ?? 'After approval' }}</dd>
                </dl>
                <label class="form-label" for="request_note">Request note</label>
                <textarea class="form-control mb-3" id="request_note" name="request_note" rows="3">{{ old('request_note') }}</textarea>
                <div class="form-check mb-3"><input class="form-check-input" id="terms" name="terms" type="checkbox" value="1" required><label class="form-check-label" for="terms">I understand the plan changes only after Super Admin approval and payment verification where required.</label></div>
                <div class="d-flex flex-wrap gap-2"><button class="btn btn-primary" type="submit" @disabled($hasBlockingUsage)><i class="fa-solid fa-paper-plane"></i>Submit Plan Change Request</button><a class="btn btn-outline-primary" href="{{ route('company-admin.subscription.index') }}">Keep Current Plan</a></div>
            </section>
        </div>
    </div>
</form>
@endsection
