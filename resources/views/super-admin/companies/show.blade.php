@extends('layouts.app')

@section('title', $company->name.' - Elevanix')

@php
    $activeTab = request('tab', 'overview');
    $tabs = [
        'overview' => 'Overview',
        'users' => 'Users',
        'projects' => 'Projects',
        'subscription' => 'Subscription',
        'payments' => 'Payments',
        'activity' => 'Activity',
    ];
@endphp

@section('content')
@include('partials.page-header', [
    'eyebrow' => 'Company Workspace',
    'title' => $company->name,
    'description' => 'Review this company\'s platform presence without entering its tenant workspace.',
    'badge' => ucfirst($company->status),
    'actions' => new Illuminate\Support\HtmlString('<a class="btn btn-outline-primary" href="'.route('super-admin.companies.edit', $company).'"><i class="fa-regular fa-pen-to-square"></i> Edit</a>'),
])

<div class="stat-grid mb-3">
    @include('partials.stat-card', ['label' => 'Users', 'value' => $company->users_count, 'icon' => 'fa-users'])
    @include('partials.stat-card', ['label' => 'Clients', 'value' => $company->clients_count, 'icon' => 'fa-address-book', 'type' => 'blue'])
    @include('partials.stat-card', ['label' => 'Projects', 'value' => $company->projects_count, 'icon' => 'fa-diagram-project'])
    @include('partials.stat-card', ['label' => 'Tasks', 'value' => $company->tasks_count, 'icon' => 'fa-list-check'])
    @include('partials.stat-card', ['label' => 'Subscription', 'value' => $company->activeSubscription?->plan?->name ?? 'No active plan', 'icon' => 'fa-repeat', 'type' => 'green'])
    @include('partials.stat-card', ['label' => 'Recognized Payments', 'value' => $currency.' '.number_format((float) $paymentTotal, 2), 'icon' => 'fa-sack-dollar', 'type' => 'green'])
</div>

<section class="content-card mb-3">
    <div class="company-workspace-header">
        <div>
            <h2>{{ $company->name }}</h2>
            <p>{{ $company->email }} · Created {{ $company->created_at->format('M d, Y') }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @include('partials.status-badge', ['status' => $company->status])
            @if($company->activeSubscription)
                @include('partials.status-badge', ['status' => $company->activeSubscription->status, 'label' => 'Subscription '.$company->activeSubscription->status])
            @endif
        </div>
    </div>

    <div class="company-tab-nav" role="tablist" aria-label="Company sections">
        @foreach($tabs as $key => $label)
            <a class="{{ $activeTab === $key ? 'active' : '' }}" href="{{ route('super-admin.companies.show', [$company, 'tab' => $key]) }}" @if($activeTab === $key) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </div>

    @if($activeTab === 'overview')
        <div class="row g-3 mt-1">
            <div class="col-lg-6">
                <section class="app-card h-100">
                    <h3>Company Information</h3>
                    <dl class="detail-list mt-3">
                        <dt>Email</dt><dd>{{ $company->email }}</dd>
                        <dt>Phone</dt><dd>{{ $company->phone ?? 'Not set' }}</dd>
                        <dt>Website</dt><dd>{{ $company->website ?? 'Not set' }}</dd>
                        <dt>Address</dt><dd>{{ $company->address ?? 'Not set' }}</dd>
                        <dt>Timezone</dt><dd>{{ $company->timezone ?? 'Platform default' }}</dd>
                    </dl>
                </section>
            </div>
            <div class="col-lg-6">
                <section class="app-card h-100">
                    <h3>Administrator</h3>
                    <dl class="detail-list mt-3">
                        <dt>Name</dt><dd>{{ $companyAdmin?->name ?? 'Not assigned' }}</dd>
                        <dt>Email</dt><dd>{{ $companyAdmin?->email ?? '-' }}</dd>
                        <dt>Username</dt><dd>{{ $companyAdmin?->username ?? '-' }}</dd>
                        <dt>Status</dt><dd>@if($companyAdmin) @include('partials.status-badge', ['status' => $companyAdmin->status]) @else - @endif</dd>
                        <dt>Last Login</dt><dd>{{ $companyAdmin?->last_login_at?->diffForHumans() ?? 'Never' }}</dd>
                    </dl>
                </section>
            </div>
            <div class="col-lg-6">
                <section class="app-card h-100">
                    <h3>Operational Summary</h3>
                    <dl class="detail-list mt-3">
                        <dt>Open Projects</dt><dd>{{ $summary['open_projects'] }}</dd>
                        <dt>Open Tasks</dt><dd>{{ $summary['open_tasks'] }}</dd>
                        <dt>Clients</dt><dd>{{ $summary['clients'] }}</dd>
                        <dt>Subscriptions</dt><dd>{{ $company->subscriptions_count }}</dd>
                    </dl>
                </section>
            </div>
            <div class="col-lg-6">
                <section class="app-card h-100">
                    <h3>Status Controls</h3>
                    <div class="d-flex flex-column gap-3 mt-3">
                        @if($company->status === 'pending')
                            <form method="POST" action="{{ route('super-admin.companies.status', [$company, 'active']) }}" data-confirm="Approve this company?" data-confirm-text="The company workspace will become active.">@csrf<button class="btn btn-outline-primary" type="submit">Approve</button></form>
                            <form method="POST" action="{{ route('super-admin.companies.status', [$company, 'rejected']) }}" data-confirm="Reject this company?" data-confirm-button="Reject company">@csrf<label class="form-label" for="reject_reason">Rejection reason</label><textarea id="reject_reason" class="form-control mb-2" name="reason" rows="2" required></textarea><button class="btn btn-outline-primary" type="submit">Reject</button></form>
                        @elseif($company->status === 'active')
                            <form method="POST" action="{{ route('super-admin.companies.status', [$company, 'suspended']) }}" data-confirm="Suspend this company?" data-confirm-text="Company administrators and employees may lose workspace access." data-confirm-button="Suspend company">@csrf<label class="form-label" for="suspend_reason">Suspension reason</label><textarea id="suspend_reason" class="form-control mb-2" name="reason" rows="2" required></textarea><button class="btn btn-outline-primary" type="submit">Suspend</button></form>
                        @elseif($company->status === 'suspended')
                            <form method="POST" action="{{ route('super-admin.companies.status', [$company, 'active']) }}" data-confirm="Reactivate this company?" data-confirm-button="Reactivate company">@csrf<button class="btn btn-outline-primary" type="submit">Reactivate</button></form>
                        @else
                            @include('partials.empty-state', ['icon' => 'fa-building-circle-xmark', 'title' => 'No status actions', 'message' => 'Rejected companies cannot be changed from this page.'])
                        @endif
                    </div>
                </section>
            </div>
        </div>
    @elseif($activeTab === 'users')
        <div class="table-responsive mt-3">
            <table class="table align-middle app-table">
                <thead><tr><th>User</th><th>Email</th><th>Role</th><th>Status</th><th>Last Login</th><th>Created</th><th></th></tr></thead>
                <tbody>
                    @forelse($users as $user)
                        <tr><td>{{ $user->name }}<small>{{ $user->username }}</small></td><td>{{ $user->email }}</td><td>{{ str_replace('_', ' ', ucfirst($user->role)) }}</td><td>@include('partials.status-badge', ['status' => $user->status])</td><td>{{ $user->last_login_at?->diffForHumans() ?? 'Never' }}</td><td>{{ $user->created_at->format('M d, Y') }}</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('super-admin.users.show', $user) }}">View</a></td></tr>
                    @empty
                        <tr><td colspan="7" class="empty-cell">@include('partials.empty-state', ['icon' => 'fa-users', 'title' => 'No users found', 'message' => 'Company users will appear here.'])</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $users->appends(['tab' => 'users'])->links() }}
    @elseif($activeTab === 'projects')
        <div class="table-responsive mt-3">
            <table class="table align-middle app-table">
                <thead><tr><th>Project</th><th>Client</th><th>Status</th><th>Progress</th><th>Deadline</th><th>Created</th></tr></thead>
                <tbody>
                    @forelse($projects as $project)
                        <tr><td>{{ $project->name }}</td><td>{{ $project->client?->name ?? '-' }}</td><td>@include('partials.status-badge', ['status' => $project->status])</td><td>{{ $project->progress ?? 0 }}%</td><td>{{ $project->due_date?->format('M d, Y') ?? '-' }}</td><td>{{ $project->created_at->format('M d, Y') }}</td></tr>
                    @empty
                        <tr><td colspan="6" class="empty-cell">@include('partials.empty-state', ['icon' => 'fa-diagram-project', 'title' => 'No projects found', 'message' => 'Company projects will appear here.'])</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $projects->appends(['tab' => 'projects'])->links() }}
    @elseif($activeTab === 'subscription')
        <div class="row g-3 mt-1">
            <div class="col-lg-6">
                <section class="app-card h-100">
                    <h3>Current Subscription</h3>
                    <dl class="detail-list mt-3">
                        <dt>Plan</dt><dd>{{ $company->activeSubscription?->plan?->name ?? 'No active plan' }}</dd>
                        <dt>Status</dt><dd>@if($company->activeSubscription) @include('partials.status-badge', ['status' => $company->activeSubscription->status]) @else - @endif</dd>
                        <dt>Starts</dt><dd>{{ $company->activeSubscription?->starts_at?->format('M d, Y') ?? '-' }}</dd>
                        <dt>Renews</dt><dd>{{ $company->activeSubscription?->renews_at?->format('M d, Y') ?? '-' }}</dd>
                        <dt>Monthly Price</dt><dd>{{ $currency }} {{ number_format((float) ($company->activeSubscription?->monthly_price ?? 0), 2) }}</dd>
                    </dl>
                </section>
            </div>
            <div class="col-lg-6">
                <section class="app-card h-100">
                    <h3>Latest Change Request</h3>
                    @if($latestChangeRequest)
                        <dl class="detail-list mt-3">
                            <dt>Requested Plan</dt><dd>{{ $latestChangeRequest->requestedPlan?->name ?? '-' }}</dd>
                            <dt>Type</dt><dd>{{ str_replace('_', ' ', $latestChangeRequest->change_type) }}</dd>
                            <dt>Status</dt><dd>@include('partials.status-badge', ['status' => $latestChangeRequest->status])</dd>
                            <dt>Requested</dt><dd>{{ $latestChangeRequest->created_at->format('M d, Y') }}</dd>
                        </dl>
                        <a class="btn btn-sm btn-outline-primary mt-2" href="{{ route('super-admin.subscription-change-requests.show', $latestChangeRequest) }}">Review Request</a>
                    @else
                        @include('partials.empty-state', ['icon' => 'fa-code-compare', 'title' => 'No plan-change requests', 'message' => 'Plan changes submitted by Company Admins will appear here.'])
                    @endif
                </section>
            </div>
            <div class="col-12">
                <div class="table-responsive">
                    <table class="table align-middle app-table">
                        <thead><tr><th>Plan</th><th>Status</th><th>Starts</th><th>Renews</th><th>Ends</th><th></th></tr></thead>
                        <tbody>
                            @forelse($subscriptions as $subscription)
                                <tr><td>{{ $subscription->plan?->name ?? '-' }}</td><td>@include('partials.status-badge', ['status' => $subscription->status])</td><td>{{ $subscription->starts_at?->format('M d, Y') ?? '-' }}</td><td>{{ $subscription->renews_at?->format('M d, Y') ?? '-' }}</td><td>{{ $subscription->ends_at?->format('M d, Y') ?? '-' }}</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('super-admin.subscriptions.show', $subscription) }}">View</a></td></tr>
                            @empty
                                <tr><td colspan="6" class="empty-cell">@include('partials.empty-state', ['icon' => 'fa-repeat', 'title' => 'No subscriptions', 'message' => 'Approved company subscriptions will appear here.'])</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $subscriptions->appends(['tab' => 'subscription'])->links() }}
            </div>
        </div>
    @elseif($activeTab === 'payments')
        <div class="table-responsive mt-3">
            <table class="table align-middle app-table">
                <thead><tr><th>Reference</th><th>Plan</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th><th></th></tr></thead>
                <tbody>
                    @forelse($payments as $payment)
                        <tr><td>{{ $payment->transaction_reference ?? 'Payment #'.$payment->id }}</td><td>{{ $payment->subscriptionPlan?->name ?? '-' }}</td><td>{{ $currency }} {{ number_format((float) $payment->amount, 2) }}</td><td>{{ str_replace('_', ' ', $payment->method ?? '-') }}</td><td>@include('partials.status-badge', ['status' => $payment->status])</td><td>{{ ($payment->paid_at ?? $payment->created_at)->format('M d, Y') }}</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('super-admin.payments.show', $payment) }}">View</a></td></tr>
                    @empty
                        <tr><td colspan="7" class="empty-cell">@include('partials.empty-state', ['icon' => 'fa-money-bill', 'title' => 'No subscription payments', 'message' => 'Company subscription payments will appear here.'])</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $payments->appends(['tab' => 'payments'])->links() }}
    @else
        <div class="activity-list mt-3">
            @forelse($recentActivities as $log)
                <div>
                    <strong>{{ str_replace('_', ' ', $log->action) }}</strong>
                    <span>{{ $log->description ?? '-' }}</span>
                    <small>{{ $log->user?->name ?? 'System' }} · {{ $log->created_at->format('M d, Y h:i A') }}</small>
                </div>
            @empty
                @include('partials.empty-state', ['icon' => 'fa-clipboard-list', 'title' => 'No activity', 'message' => 'Audit activity for this company will appear here.'])
            @endforelse
        </div>
        {{ $recentActivities->appends(['tab' => 'activity'])->links() }}
    @endif
</section>
@endsection
