@extends('layouts.app')

@section('title', 'Super Admin Dashboard - Elevanix')

@section('content')
@include('partials.page-header', [
    'eyebrow' => 'Super Admin',
    'title' => 'Platform Dashboard',
    'description' => 'Monitor company growth, subscriptions, payments and platform activity from one control center.',
    'actions' => new Illuminate\Support\HtmlString(
        '<a class="btn btn-primary" href="'.route('super-admin.company-requests.index', ['status' => 'pending']).'"><i class="fa-solid fa-building-circle-check"></i> Review Requests</a>'.
        '<a class="btn btn-outline-primary" href="'.route('super-admin.revenue.index').'"><i class="fa-solid fa-chart-line"></i> Revenue</a>'.
        '<a class="btn btn-outline-primary" href="'.route('super-admin.reports.index').'"><i class="fa-solid fa-chart-pie"></i> Reports</a>'
    ),
])

<div class="stat-grid mb-3">
    @foreach($primaryStats as $stat)
        @include('partials.stat-card', [
            'label' => $stat['label'],
            'value' => $stat['value'],
            'icon' => $stat['icon'],
            'type' => $stat['type'],
        ])
    @endforeach
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-8">
        <section class="content-card h-100">
            <div class="content-card-header">
                <div>
                    <h2>Platform Growth</h2>
                    <p>Companies and users added over the last six months.</p>
                </div>
            </div>
            <div class="dashboard-chart-wrapper chart-large">
                <canvas id="superAdminCompanyGrowthChart" data-chart="companyGrowth" role="img" aria-label="Company growth chart"></canvas>
            </div>
        </section>
    </div>
    <div class="col-xl-4">
        <section class="content-card h-100">
            <div class="content-card-header">
                <div>
                    <h2>Company Status</h2>
                    <p>Current platform access state.</p>
                </div>
            </div>
            <div class="dashboard-pie-wrapper chart-medium">
                <canvas id="superAdminCompanyStatusChart" data-chart="companyStatus" role="img" aria-label="Company status distribution chart"></canvas>
            </div>
        </section>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-4">
        <section class="content-card h-100">
            <div class="content-card-header">
                <div>
                    <h2>Needs Attention</h2>
                    <p>Queues that may need Super Admin action.</p>
                </div>
            </div>
            <div class="attention-list">
                @foreach($attentionItems as $item)
                    <a class="attention-item attention-{{ $item['tone'] }}" href="{{ $item['url'] }}">
                        <span><i class="fa-solid {{ $item['icon'] }}" aria-hidden="true"></i></span>
                        <strong>{{ $item['count'] }}</strong>
                        <small>{{ $item['label'] }}</small>
                    </a>
                @endforeach
            </div>
        </section>
    </div>
    <div class="col-lg-4">
        <section class="content-card h-100">
            <div class="content-card-header">
                <div>
                    <h2>Subscription Revenue</h2>
                    <p>Recognized subscription payments by month.</p>
                </div>
            </div>
            <div class="dashboard-chart-wrapper chart-compact">
                <canvas id="superAdminRevenueGrowthChart" data-chart="revenueGrowth" role="img" aria-label="Subscription revenue chart"></canvas>
            </div>
        </section>
    </div>
    <div class="col-lg-4">
        <section class="content-card h-100">
            <div class="content-card-header">
                <div>
                    <h2>Plan Usage</h2>
                    <p>Companies grouped by subscription plan.</p>
                </div>
            </div>
            <div class="dashboard-pie-wrapper chart-medium">
                <canvas id="superAdminPlanUsageChart" data-chart="planUsage" role="img" aria-label="Subscription plan usage chart"></canvas>
            </div>
        </section>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-8">
        <section class="content-card h-100">
            <div class="content-card-header">
                <div>
                    <h2>Recent Companies</h2>
                    <p>Newest organizations on Elevanix.</p>
                </div>
                <a class="btn btn-sm btn-outline-primary" href="{{ route('super-admin.companies.index') }}">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table align-middle app-table">
                    <thead>
                        <tr>
                            <th>Company</th>
                            <th>Admin</th>
                            <th>Users</th>
                            <th>Projects</th>
                            <th>Subscription</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentCompanies as $company)
                            @php($admin = $company->users->first())
                            <tr>
                                <td>{{ $company->name }}<small>{{ $company->created_at->format('M d, Y') }}</small></td>
                                <td>{{ $admin?->name ?? 'Not assigned' }}<small>{{ $admin?->email }}</small></td>
                                <td>{{ $company->users_count }}</td>
                                <td>{{ $company->projects_count }}</td>
                                <td>{{ $company->activeSubscription?->plan?->name ?? 'No plan' }}</td>
                                <td>@include('partials.status-badge', ['status' => $company->status])</td>
                                <td><a class="btn btn-sm btn-outline-primary" href="{{ route('super-admin.companies.show', $company) }}"><i class="fa-regular fa-eye"></i> View</a></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="empty-cell">
                                    @include('partials.empty-state', ['icon' => 'fa-building', 'title' => 'No companies yet', 'message' => 'Approved companies will appear here.'])
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
    <div class="col-xl-4">
        <section class="content-card h-100">
            <div class="content-card-header">
                <div>
                    <h2>Recent Platform Activity</h2>
                    <p>Latest audited administrative actions.</p>
                </div>
                <a class="btn btn-sm btn-outline-primary" href="{{ route('super-admin.audit-logs.index') }}">Audit Logs</a>
            </div>
            <div class="activity-list">
                @forelse($latestAuditLogs as $log)
                    <div>
                        <strong>{{ str_replace('_', ' ', $log->action) }}</strong>
                        <span>{{ $log->description ?? $log->module ?? 'Platform activity' }}</span>
                        <small>{{ $log->user?->name ?? 'System' }} · {{ $log->created_at->diffForHumans() }}</small>
                    </div>
                @empty
                    @include('partials.empty-state', ['icon' => 'fa-clipboard-list', 'title' => 'No audit activity', 'message' => 'Important platform activity will appear here.'])
                @endforelse
            </div>
        </section>
    </div>
</div>

<script>
    window.elevanixDashboardCharts = {
        labels: @json($chartLabels),
        companyGrowth: @json($companyGrowth),
        userGrowth: @json($userGrowth),
        revenueGrowth: @json($revenueGrowth),
        companyStatusLabels: @json($companyStatusLabels),
        companyStatusValues: @json($companyStatusValues),
        planUsageLabels: @json($planUsageLabels),
        planUsageValues: @json($planUsageValues),
    };
</script>
@endsection
