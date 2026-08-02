@extends('layouts.app')

@section('title', 'Company Subscriptions - Elevanix')

@section('content')
@include('partials.page-header', ['eyebrow' => 'Super Admin', 'title' => 'Company Subscriptions', 'description' => 'Monitor trials, active subscriptions, renewals, cancellations and expiring accounts.'])

<section class="content-card">
    <form class="row g-3 mb-3" method="GET">
        <div class="col-md-4"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Search company"></div>
        <div class="col-md-3"><select class="form-select" name="plan"><option value="">All plans</option>@foreach($plans as $plan)<option value="{{ $plan->id }}" @selected(request('plan') == $plan->id)>{{ $plan->name }}</option>@endforeach</select></div>
        <div class="col-md-3"><select class="form-select" name="status"><option value="">All statuses</option>@foreach($statuses as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>@endforeach</select></div>
        <div class="col-md-2"><button class="btn btn-primary w-100" type="submit"><i class="fa-solid fa-filter"></i> Filter</button></div>
    </form>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Company</th><th>Plan</th><th>Dates</th><th>Price</th><th>Status</th><th>Warning</th><th></th></tr></thead>
            <tbody>
                @forelse($subscriptions as $subscription)
                    @php($days = $subscription->renews_at ? now()->diffInDays($subscription->renews_at, false) : null)
                    <tr>
                        <td>{{ $subscription->company?->name }}<small>{{ $subscription->company?->email }}</small></td>
                        <td>{{ $subscription->plan?->name }}</td>
                        <td>Starts {{ $subscription->starts_at?->format('M d, Y') }}<small>Renews {{ $subscription->renews_at?->format('M d, Y') ?? '-' }}</small></td>
                        <td>${{ number_format($subscription->monthly_price, 2) }}</td>
                        <td>@include('partials.status-badge', ['status' => $subscription->status])</td>
                        <td>@if($days !== null && $days < 0)<span class="text-danger fw-bold">Expired</span>@elseif($days !== null && $days <= 7)<span class="text-danger fw-bold">Within 7 days</span>@elseif($days !== null && $days <= 30)<span class="text-warning fw-bold">Within 30 days</span>@else <span class="text-muted">Healthy</span> @endif</td>
                        <td><a class="btn btn-sm btn-outline-primary" href="{{ route('super-admin.subscriptions.show', $subscription) }}"><i class="fa-regular fa-eye"></i> View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty-cell">@include('partials.empty-state', ['icon' => 'fa-repeat', 'title' => 'No subscriptions found', 'message' => 'Approved company subscriptions will appear here.'])</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $subscriptions->links() }}
</section>
@endsection
