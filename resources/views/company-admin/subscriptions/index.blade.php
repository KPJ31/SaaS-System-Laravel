@extends('layouts.app')

@section('title', 'Subscription Information - Elevanix')

@section('content')
@include('partials.page-header', [
    'eyebrow' => 'Company Subscription',
    'title' => 'Subscription Information',
    'description' => 'Review your current plan, compare active plans, and request a secure plan change.',
])

<div class="row g-3 mb-3">
    <div class="col-lg-5">
        <section class="content-card h-100">
            <div class="content-card-header"><div><h2>Current Subscription</h2><p>Your live limits remain active until Super Admin approval.</p></div></div>
            @if($subscription)
                <dl class="detail-list">
                    <dt>Current Plan</dt><dd>{{ $subscription->plan?->name ?? 'Plan unavailable' }}</dd>
                    <dt>Status</dt><dd>@include('partials.status-badge', ['status' => $subscription->status])</dd>
                    <dt>Starts</dt><dd>{{ $subscription->starts_at?->format('M d, Y') ?? '-' }}</dd>
                    <dt>Renews</dt><dd>{{ $subscription->renews_at?->format('M d, Y') ?? '-' }}</dd>
                    <dt>Monthly Price</dt><dd>${{ number_format($subscription->monthly_price ?? 0, 2) }}</dd>
                    <dt>Employees</dt><dd>{{ $usage['employees'] }} / {{ $subscription->plan?->employee_limit ?? '-' }}</dd>
                    <dt>Projects</dt><dd>{{ $usage['projects'] }} / {{ $subscription->plan?->project_limit ?? '-' }}</dd>
                    <dt>Storage</dt><dd>{{ $usage['storage_mb'] }} MB / {{ $subscription->plan?->storage_limit_mb ?? '-' }} MB</dd>
                </dl>
            @else
                @include('partials.empty-state', ['icon' => 'fa-credit-card', 'title' => 'No subscription found', 'message' => 'Contact the Super Admin to attach a subscription before requesting a change.'])
            @endif
        </section>
    </div>
    <div class="col-lg-7">
        <section class="content-card h-100">
            <div class="content-card-header"><div><h2>Plan-Change Status</h2><p>Only one active request is allowed per company.</p></div></div>
            @if($pendingRequest)
                <dl class="detail-list">
                    <dt>Requested Plan</dt><dd>{{ $pendingRequest->requestedPlan?->name }}</dd>
                    <dt>Change Type</dt><dd>@include('partials.status-badge', ['status' => $pendingRequest->change_type])</dd>
                    <dt>Amount Due</dt><dd>${{ number_format($pendingRequest->payable_amount, 2) }}</dd>
                    <dt>Status</dt><dd>@include('partials.status-badge', ['status' => $pendingRequest->status])</dd>
                    <dt>Payment</dt><dd>{{ $pendingRequest->payment?->status ? str_replace('_', ' ', $pendingRequest->payment->status) : 'Not submitted' }}</dd>
                    <dt>Review Note</dt><dd>{{ $pendingRequest->review_note ?? '-' }}</dd>
                </dl>
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-outline-primary" href="{{ route('company-admin.subscription.change.show', $pendingRequest) }}"><i class="fa-regular fa-eye"></i>View Request</a>
                    @if($pendingRequest->status === 'payment_required')
                        <a class="btn btn-primary" href="{{ route('company-admin.subscription.change.payment', $pendingRequest) }}"><i class="fa-solid fa-upload"></i>Submit Proof</a>
                    @endif
                    @if($pendingRequest->canBeCancelled())
                        <form method="POST" action="{{ route('company-admin.subscription.change.cancel', $pendingRequest) }}" data-confirm="Cancel this plan-change request?">@csrf<button class="btn btn-outline-danger" type="submit"><i class="fa-solid fa-ban"></i>Cancel Request</button></form>
                    @endif
                </div>
            @else
                @include('partials.empty-state', ['icon' => 'fa-arrows-rotate', 'title' => 'No active request', 'message' => 'Choose a plan below when you are ready to request a change.'])
            @endif
        </section>
    </div>
</div>

<section class="content-card mb-3">
    <div class="content-card-header"><div><h2>Available Plans</h2><p>Server-side pricing is used for every request. Browser prices are ignored.</p></div></div>
    <div class="plan-card-grid">
        @forelse($plans as $plan)
            @php
                $isCurrent = $subscription && (int) $subscription->subscription_plan_id === (int) $plan->id;
                $currentPrice = (float) ($subscription?->plan?->monthly_price ?? 0);
                $changeLabel = $isCurrent ? 'Current Plan' : (((float) $plan->monthly_price > $currentPrice) ? 'Upgrade' : (((float) $plan->monthly_price < $currentPrice) ? 'Downgrade' : 'Change'));
                $isRecommended = ! $isCurrent && $plans->sortByDesc('monthly_price')->first()?->id === $plan->id;
            @endphp
            <article class="plan-card {{ $isCurrent ? 'plan-card-current' : '' }} {{ $isRecommended ? 'plan-card-recommended' : '' }}">
                <div class="plan-card-top">
                    <div>
                        <h3>{{ $plan->name }}</h3>
                        <p>{{ $plan->description ?? 'Flexible SaaS workspace plan.' }}</p>
                    </div>
                    <span class="status-badge status-{{ $isCurrent ? 'info' : ($changeLabel === 'Downgrade' ? 'warning' : 'success') }}"><span></span>{{ $changeLabel }}</span>
                </div>
                @if($isRecommended)<span class="plan-recommended-badge">Recommended</span>@endif
                <div class="plan-price">${{ number_format($plan->monthly_price, 2) }}<small>/month</small></div>
                <div class="plan-limits">
                    <span><strong>{{ $plan->employee_limit }}</strong> employees</span>
                    <span><strong>{{ $plan->project_limit }}</strong> projects</span>
                    <span><strong>{{ $plan->storage_limit_mb }}</strong> MB storage</span>
                </div>
                <ul class="check-list">
                    @forelse(array_slice($plan->features ?? [], 0, 5) as $feature)
                        <li><i class="fa-solid fa-check"></i>{{ $feature }}</li>
                    @empty
                        <li><i class="fa-solid fa-check"></i>Core company management tools</li>
                    @endforelse
                </ul>
                @if($isCurrent)
                    <button class="btn btn-outline-primary w-100" disabled>Current Plan</button>
                @elseif($pendingRequest)
                    <button class="btn btn-outline-primary w-100" disabled>Request In Progress</button>
                @else
                    <a class="btn btn-primary w-100" href="{{ route('company-admin.subscription.change.create', $plan) }}"><i class="fa-solid fa-arrow-right"></i>Select Plan</a>
                @endif
            </article>
        @empty
            @include('partials.empty-state', ['icon' => 'fa-layer-group', 'title' => 'No active plans', 'message' => 'Active subscription plans will appear here once Super Admin enables them.'])
        @endforelse
    </div>
</section>

<section class="content-card">
    <div class="content-card-header"><div><h2>Subscription History</h2><p>Previous and current plan-change requests for your company.</p></div></div>
    <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Requested Plan</th><th>Type</th><th>Billing</th><th>Amount</th><th>Status</th><th>Requested</th><th>Reviewed</th><th></th></tr></thead><tbody>
        @forelse($history as $request)
            <tr><td>{{ $request->requestedPlan?->name ?? '-' }}<small>From {{ $request->currentPlan?->name ?? '-' }}</small></td><td>{{ str_replace('_', ' ', $request->change_type) }}</td><td>{{ ucfirst($request->billing_cycle) }}</td><td>${{ number_format($request->payable_amount, 2) }}</td><td>@include('partials.status-badge', ['status' => $request->status])</td><td>{{ $request->created_at->format('M d, Y') }}</td><td>{{ $request->reviewed_at?->format('M d, Y') ?? '-' }}</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('company-admin.subscription.change.show', $request) }}">View</a></td></tr>
        @empty
            <tr><td colspan="8" class="empty-cell">No plan-change history yet.</td></tr>
        @endforelse
    </tbody></table></div>{{ $history->links() }}
</section>
@endsection
