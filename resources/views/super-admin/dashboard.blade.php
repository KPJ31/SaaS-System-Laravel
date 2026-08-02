@extends('layouts.app')

@section('title', 'Super Admin Dashboard - Elevanix')

@section('content')
@include('partials.page-header', [
    'eyebrow' => 'Super Admin',
    'title' => 'Platform Dashboard',
    'description' => 'Monitor companies, subscriptions, revenue, projects, employees and platform activity from one calm control center.',
    'actions' => new Illuminate\Support\HtmlString(
        '<a class="btn btn-primary" href="'.route('super-admin.company-requests.index').'"><i class="fa-solid fa-building-circle-check"></i> Review Requests</a>'.
        '<a class="btn btn-outline-primary" href="'.route('super-admin.subscription-plans.create').'"><i class="fa-solid fa-plus"></i> Create Plan</a>'.
        '<a class="btn btn-outline-primary" href="'.route('super-admin.reports.index').'"><i class="fa-solid fa-chart-pie"></i> Reports</a>'
    ),
])

<div class="stat-grid">
    @include('partials.stat-card', ['label' => 'Total Companies', 'value' => $companiesCount, 'icon' => 'fa-building'])
    @include('partials.stat-card', ['label' => 'Active Companies', 'value' => $activeCompaniesCount, 'icon' => 'fa-building-circle-check', 'tone' => 'green'])
    @include('partials.stat-card', ['label' => 'Pending Companies', 'value' => $pendingCompaniesCount + $pendingRequestsCount, 'icon' => 'fa-clock', 'tone' => 'yellow'])
    @include('partials.stat-card', ['label' => 'Suspended Companies', 'value' => $suspendedCompaniesCount, 'icon' => 'fa-ban'])
    @include('partials.stat-card', ['label' => 'Rejected Companies', 'value' => $rejectedCompaniesCount, 'icon' => 'fa-circle-xmark'])
    @include('partials.stat-card', ['label' => 'Company Admins', 'value' => $companyAdminsCount, 'icon' => 'fa-user-tie', 'tone' => 'blue'])
    @include('partials.stat-card', ['label' => 'Plans', 'value' => $subscriptionPlansCount, 'icon' => 'fa-layer-group'])
    @include('partials.stat-card', ['label' => 'Active Subscriptions', 'value' => $activeSubscriptionsCount, 'icon' => 'fa-repeat', 'tone' => 'blue'])
    @include('partials.stat-card', ['label' => 'Expired Subscriptions', 'value' => $expiredSubscriptionsCount, 'icon' => 'fa-calendar-xmark'])
    @include('partials.stat-card', ['label' => 'Monthly Revenue', 'value' => '$'.number_format($monthlyRevenue, 2), 'icon' => 'fa-chart-line'])
    @include('partials.stat-card', ['label' => 'Total Revenue', 'value' => '$'.number_format($totalRevenue, 2), 'icon' => 'fa-sack-dollar', 'tone' => 'green'])
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-6"><section class="content-card"><h2>Company Growth</h2><canvas data-chart="companyGrowth"></canvas></section></div>
    <div class="col-lg-6"><section class="content-card"><h2>Subscription Revenue</h2><canvas data-chart="revenueGrowth"></canvas></section></div>
    <div class="col-lg-4"><section class="content-card"><h2>Company Status</h2><canvas data-chart="companyStatus"></canvas></section></div>
    <div class="col-lg-4"><section class="content-card"><h2>Plan Usage</h2><canvas data-chart="planUsage"></canvas></section></div>
</div>

<div class="row g-3">
    @foreach([
        ['Recently Registered Companies', $recentCompanies, 'name', 'email', 'super-admin.companies.show'],
        ['Pending Company Approvals', $latestRequests, 'company_name', 'admin_email', 'super-admin.company-requests.show'],
        ['Recent Subscription Payments', $recentPayments, 'transaction_reference', 'amount', 'super-admin.payments.show'],
    ] as [$heading, $rows, $main, $sub, $routeName])
        <div class="col-xl-4 col-lg-6">
            <section class="content-card h-100">
                <div class="content-card-header"><h2>{{ $heading }}</h2></div>
                <div class="activity-list">
                    @forelse($rows as $row)
                        <div>
                            <strong>{{ $row->{$main} ?: 'Reference #'.$row->id }}</strong>
                            <span>{{ $sub === 'amount' ? '$'.number_format($row->{$sub}, 2) : str_replace('_', ' ', (string) $row->{$sub}) }}</span>
                            <a class="small" href="{{ route($routeName, $row) }}">View</a>
                        </div>
                    @empty
                        @include('partials.empty-state', ['icon' => 'fa-circle-info', 'title' => 'No records', 'message' => 'New activity will appear here.'])
                    @endforelse
                </div>
            </section>
        </div>
    @endforeach
    <div class="col-xl-4 col-lg-6">
        <section class="content-card h-100">
            <div class="content-card-header"><h2>Latest System Activities</h2></div>
            <div class="activity-list">
                @forelse($latestAuditLogs as $log)
                    <div><strong>{{ str_replace('_', ' ', $log->action) }}</strong><span>{{ $log->description }}</span></div>
                @empty
                    @include('partials.empty-state', ['icon' => 'fa-clipboard-list', 'title' => 'No audit activity', 'message' => 'System activity will be listed here.'])
                @endforelse
            </div>
        </section>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    window.elevanixDashboardCharts = {
        labels: @json($chartLabels),
        companyGrowth: @json($companyGrowth),
        revenueGrowth: @json($revenueGrowth),
        companyStatusLabels: @json($companyStatusLabels),
        companyStatusValues: @json($companyStatusValues),
        planUsageLabels: @json($planUsageLabels),
        planUsageValues: @json($planUsageValues),
    };
</script>
@endsection
