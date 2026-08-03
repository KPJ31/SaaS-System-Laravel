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
    @include('partials.stat-card', ['label' => 'Employees', 'value' => $employeesCount, 'icon' => 'fa-users'])
    @include('partials.stat-card', ['label' => 'Projects', 'value' => $projectsCount, 'icon' => 'fa-diagram-project', 'tone' => 'blue'])
    @include('partials.stat-card', ['label' => 'Active Projects', 'value' => $activeProjectsCount, 'icon' => 'fa-bolt', 'tone' => 'green'])
    @include('partials.stat-card', ['label' => 'Completed Projects', 'value' => $completedProjectsCount, 'icon' => 'fa-circle-check', 'tone' => 'green'])
    @include('partials.stat-card', ['label' => 'Plans', 'value' => $subscriptionPlansCount, 'icon' => 'fa-layer-group'])
    @include('partials.stat-card', ['label' => 'Active Subscriptions', 'value' => $activeSubscriptionsCount, 'icon' => 'fa-repeat', 'tone' => 'blue'])
    @include('partials.stat-card', ['label' => 'Expired Subscriptions', 'value' => $expiredSubscriptionsCount, 'icon' => 'fa-calendar-xmark'])
    @include('partials.stat-card', ['label' => 'Pending Payments', 'value' => $pendingSubscriptionPaymentsCount, 'icon' => 'fa-credit-card', 'tone' => 'yellow'])
    @include('partials.stat-card', ['label' => 'Unread Alerts', 'value' => $unreadNotificationsCount, 'icon' => 'fa-bell'])
    @include('partials.stat-card', ['label' => 'Monthly Revenue', 'value' => '$'.number_format($monthlyRevenue, 2), 'icon' => 'fa-chart-line'])
    @include('partials.stat-card', ['label' => 'Total Revenue', 'value' => '$'.number_format($totalRevenue, 2), 'icon' => 'fa-sack-dollar', 'tone' => 'green'])
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-6"><section class="content-card h-100"><h2>Company Growth</h2><div class="dashboard-chart-wrapper"><canvas id="superAdminCompanyGrowthChart" data-chart="companyGrowth" role="img" aria-label="Monthly company registration growth chart"></canvas></div></section></div>
    <div class="col-lg-6"><section class="content-card h-100"><h2>Subscription Revenue</h2><div class="dashboard-chart-wrapper"><canvas id="superAdminRevenueGrowthChart" data-chart="revenueGrowth" role="img" aria-label="Monthly subscription revenue chart"></canvas></div></section></div>
    <div class="col-lg-4"><section class="content-card h-100"><h2>Company Status</h2><div class="dashboard-pie-wrapper chart-medium"><canvas id="superAdminCompanyStatusChart" data-chart="companyStatus" role="img" aria-label="Company status distribution chart"></canvas></div></section></div>
    <div class="col-lg-4"><section class="content-card h-100"><h2>Plan Usage</h2><div class="dashboard-pie-wrapper chart-medium"><canvas id="superAdminPlanUsageChart" data-chart="planUsage" role="img" aria-label="Subscription plan usage chart"></canvas></div></section></div>
    <div class="col-lg-4"><section class="content-card h-100"><h2>Project Status</h2><div class="dashboard-pie-wrapper chart-medium"><canvas id="superAdminProjectStatusChart" data-chart="platformProjectStatus" role="img" aria-label="Platform project status distribution chart"></canvas></div></section></div>
    <div class="col-lg-6"><section class="content-card h-100"><h2>User Growth</h2><div class="dashboard-chart-wrapper"><canvas id="superAdminUserGrowthChart" data-chart="platformUserGrowth" role="img" aria-label="Monthly platform user growth chart"></canvas></div></section></div>
    <div class="col-lg-6"><section class="content-card h-100"><h2>Task Status</h2><div class="dashboard-pie-wrapper chart-medium"><canvas id="superAdminTaskStatusChart" data-chart="platformTaskStatus" role="img" aria-label="Platform task status distribution chart"></canvas></div></section></div>
</div>

<div class="row g-3">
    @foreach([
        ['Recently Registered Companies', $recentCompanies, 'name', 'email', 'super-admin.companies.show'],
        ['Pending Company Approvals', $latestRequests, 'company_name', 'admin_email', 'super-admin.company-requests.show'],
        ['Recent Subscription Payments', $recentPayments, 'transaction_reference', 'amount', 'super-admin.payments.show'],
        ['Recent Projects', $recentProjects, 'name', 'status', 'super-admin.reports.show'],
        ['Latest Platform Users', $latestUsers, 'name', 'email', 'super-admin.users.show'],
    ] as [$heading, $rows, $main, $sub, $routeName])
        <div class="col-xl-4 col-lg-6">
            <section class="content-card h-100">
                <div class="content-card-header"><h2>{{ $heading }}</h2></div>
                <div class="activity-list">
                    @forelse($rows as $row)
                        <div>
                            <strong>{{ $row->{$main} ?: 'Reference #'.$row->id }}</strong>
                            <span>{{ $sub === 'amount' ? '$'.number_format($row->{$sub}, 2) : str_replace('_', ' ', (string) $row->{$sub}) }}</span>
                            <a class="small" href="{{ $routeName === 'super-admin.reports.show' ? route($routeName, 'projects') : route($routeName, $row) }}">View</a>
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
<script>
    window.elevanixDashboardCharts = {
        labels: @json($chartLabels),
        companyGrowth: @json($companyGrowth),
        revenueGrowth: @json($revenueGrowth),
        companyStatusLabels: @json($companyStatusLabels),
        companyStatusValues: @json($companyStatusValues),
        planUsageLabels: @json($planUsageLabels),
        planUsageValues: @json($planUsageValues),
        projectStatusLabels: @json($projectStatusLabels),
        projectStatusValues: @json($projectStatusValues),
        taskStatusLabels: @json($taskStatusLabels),
        taskStatusValues: @json($taskStatusValues),
        userGrowth: @json($userGrowth),
    };
</script>
@endsection
