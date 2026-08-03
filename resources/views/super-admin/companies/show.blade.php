@extends('layouts.app')

@section('title', $company->name.' - Elevanix')

@section('content')
@include('partials.page-header', [
    'eyebrow' => 'Company Details',
    'title' => $company->name,
    'description' => 'Review company profile, subscription, users, projects, payments and recent activity.',
    'actions' => new Illuminate\Support\HtmlString('<a class="btn btn-outline-primary" href="'.route('super-admin.companies.edit', $company).'"><i class="fa-regular fa-pen-to-square"></i> Edit</a>'),
])

<div class="stat-grid">
    @include('partials.stat-card', ['label' => 'Users', 'value' => $company->users->count(), 'icon' => 'fa-users'])
    @include('partials.stat-card', ['label' => 'Subscriptions', 'value' => $company->subscriptions->count(), 'icon' => 'fa-repeat'])
    @include('partials.stat-card', ['label' => 'Payments', 'value' => '$'.number_format($company->payments->sum('amount'), 2), 'icon' => 'fa-sack-dollar', 'tone' => 'green'])
    @include('partials.stat-card', ['label' => 'Status', 'value' => ucfirst($company->status), 'icon' => 'fa-building-circle-check'])
</div>

<div class="row g-3">
    <div class="col-lg-6"><section class="content-card"><h2>Basic Information</h2><dl class="detail-list mt-3"><dt>Email</dt><dd>{{ $company->email }}</dd><dt>Phone</dt><dd>{{ $company->phone ?? 'Not set' }}</dd><dt>Website</dt><dd>{{ $company->website ?? 'Not set' }}</dd><dt>Address</dt><dd>{{ $company->address ?? 'Not set' }}</dd></dl></section></div>
    <div class="col-lg-6"><section class="content-card"><h2>Company Admin</h2><dl class="detail-list mt-3"><dt>Name</dt><dd>{{ $companyAdmin?->name ?? 'Not assigned' }}</dd><dt>Email</dt><dd>{{ $companyAdmin?->email ?? '-' }}</dd><dt>Status</dt><dd>{{ $companyAdmin?->status ?? '-' }}</dd></dl></section></div>
    <div class="col-lg-6"><section class="content-card"><h2>Current Subscription</h2><dl class="detail-list mt-3"><dt>Plan</dt><dd>{{ $company->activeSubscription?->plan?->name ?? 'No active plan' }}</dd><dt>Status</dt><dd>{{ $company->activeSubscription?->status ?? '-' }}</dd><dt>Renews</dt><dd>{{ $company->activeSubscription?->renews_at?->format('M d, Y') ?? '-' }}</dd><dt>Monthly Price</dt><dd>${{ number_format($company->activeSubscription?->monthly_price ?? 0, 2) }}</dd></dl></section></div>
    <div class="col-lg-6">
        <section class="content-card">
            <h2>Status Controls</h2>
            <div class="d-flex flex-column gap-3 mt-3">
                @if($company->status === 'pending')
                    <form method="POST" action="{{ route('super-admin.companies.status', [$company, 'active']) }}" data-confirm="Approve this company?">@csrf<button class="btn btn-outline-primary" type="submit">Approve</button></form>
                    <form method="POST" action="{{ route('super-admin.companies.status', [$company, 'rejected']) }}" data-confirm="Reject this company?">@csrf<label class="form-label" for="reject_reason">Rejection reason</label><textarea id="reject_reason" class="form-control mb-2" name="reason" rows="2" required></textarea><button class="btn btn-outline-primary" type="submit">Reject</button></form>
                @elseif($company->status === 'active')
                    <form method="POST" action="{{ route('super-admin.companies.status', [$company, 'suspended']) }}" data-confirm="Suspend this company?">@csrf<label class="form-label" for="suspend_reason">Suspension reason</label><textarea id="suspend_reason" class="form-control mb-2" name="reason" rows="2" required></textarea><button class="btn btn-outline-primary" type="submit">Suspend</button></form>
                @elseif($company->status === 'suspended')
                    <form method="POST" action="{{ route('super-admin.companies.status', [$company, 'active']) }}" data-confirm="Reactivate this company?">@csrf<button class="btn btn-outline-primary" type="submit">Reactivate</button></form>
                @else
                    @include('partials.empty-state', ['icon' => 'fa-building-circle-xmark', 'title' => 'No status actions', 'message' => 'Rejected companies cannot be changed from this page.'])
                @endif
            </div>
        </section>
    </div>
    <div class="col-lg-6"><section class="content-card"><h2>Recent Activity</h2><div class="activity-list mt-3">@forelse($recentActivities as $log)<div><strong>{{ str_replace('_', ' ', $log->action) }}</strong><span>{{ $log->description }}</span></div>@empty @include('partials.empty-state', ['icon' => 'fa-clipboard-list', 'title' => 'No activity', 'message' => 'Audit activity will appear here.']) @endforelse</div></section></div>
</div>
@endsection
