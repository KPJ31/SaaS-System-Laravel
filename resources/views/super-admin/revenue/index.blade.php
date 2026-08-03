@extends('layouts.app')

@section('title', 'Revenue Overview - Elevanix')

@section('content')
@include('partials.page-header', [
    'eyebrow' => 'Super Admin',
    'title' => 'Revenue Overview',
    'description' => 'Recognized platform revenue uses verified subscription payments only. Pending, failed, rejected and refunded payments are tracked separately.',
    'actions' => new Illuminate\Support\HtmlString(
        '<a class="btn btn-outline-primary" href="'.route('super-admin.revenue.export.csv', request()->query()).'"><i class="fa-solid fa-file-csv"></i> Export CSV</a>'.
        '<a class="btn btn-primary" href="'.route('super-admin.revenue.export.pdf', request()->query()).'"><i class="fa-solid fa-file-pdf"></i> Export PDF</a>'
    ),
])

<div class="stat-grid">
    @include('partials.stat-card', ['label' => 'Total Platform Revenue', 'value' => $currency.' '.number_format($summary['total'], 2), 'icon' => 'fa-sack-dollar', 'tone' => 'green'])
    @include('partials.stat-card', ['label' => 'Revenue This Month', 'value' => $currency.' '.number_format($summary['month'], 2), 'icon' => 'fa-chart-line'])
    @include('partials.stat-card', ['label' => 'Revenue This Year', 'value' => $currency.' '.number_format($summary['year'], 2), 'icon' => 'fa-calendar-days'])
    @include('partials.stat-card', ['label' => 'Pending Payments', 'value' => $currency.' '.number_format($summary['pending'], 2), 'icon' => 'fa-clock', 'tone' => 'yellow'])
    @include('partials.stat-card', ['label' => 'Verified Payments', 'value' => $summary['verified_count'], 'icon' => 'fa-circle-check', 'tone' => 'green'])
    @include('partials.stat-card', ['label' => 'Refunded Payments', 'value' => $summary['refunded_count'], 'icon' => 'fa-rotate-left', 'tone' => 'yellow'])
    @include('partials.stat-card', ['label' => 'Active Subscriptions', 'value' => $summary['active_subscriptions'], 'icon' => 'fa-repeat'])
    @include('partials.stat-card', ['label' => 'Average Revenue / Company', 'value' => $currency.' '.number_format($summary['average_company'], 2), 'icon' => 'fa-building'])
</div>

<section class="content-card mb-3">
    <form class="row g-3 align-items-end" method="GET" action="{{ route('super-admin.revenue.index') }}">
        <div class="col-lg-2 col-md-4">
            <label class="form-label" for="search">Search</label>
            <input class="form-control" id="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Reference, method or company">
        </div>
        <div class="col-lg-2 col-md-4">
            <label class="form-label" for="period">Period</label>
            <select class="form-select" id="period" name="period">
                @foreach($periods as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['period'] ?? 'this_month') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-2 col-md-4">
            <label class="form-label" for="date_from">Date From</label>
            <input class="form-control" id="date_from" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
        </div>
        <div class="col-lg-2 col-md-4">
            <label class="form-label" for="date_to">Date To</label>
            <input class="form-control" id="date_to" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
        </div>
        <div class="col-lg-2 col-md-4">
            <label class="form-label" for="company_id">Company</label>
            <select class="form-select" id="company_id" name="company_id">
                <option value="">All companies</option>
                @foreach($companies as $company)
                    <option value="{{ $company->id }}" @selected(($filters['company_id'] ?? null) == $company->id)>{{ $company->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-2 col-md-4">
            <label class="form-label" for="plan_id">Subscription Plan</label>
            <select class="form-select" id="plan_id" name="plan_id">
                <option value="">All plans</option>
                @foreach($plans as $plan)
                    <option value="{{ $plan->id }}" @selected(($filters['plan_id'] ?? null) == $plan->id)>{{ $plan->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-2 col-md-4">
            <label class="form-label" for="status">Payment Status</label>
            <select class="form-select" id="status" name="status">
                <option value="">All statuses</option>
                @foreach($statuses as $status)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? null) === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-4 col-md-8">
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter"></i> Apply Filters</button>
                <a class="btn btn-outline-primary" href="{{ route('super-admin.revenue.index') }}"><i class="fa-solid fa-rotate-left"></i> Clear</a>
            </div>
        </div>
    </form>
</section>

<div class="row g-3 mb-3">
    <div class="col-xl-6">
        <section class="content-card h-100">
            <div class="content-card-header"><div><h2>Monthly Revenue Trend</h2><p>Recognized subscription revenue by revenue date.</p></div></div>
            <div class="dashboard-chart-wrapper"><canvas id="revenueMonthlyTrendChart" data-chart="revenueMonthlyTrend" role="img" aria-label="Monthly platform revenue trend chart"></canvas></div>
        </section>
    </div>
    <div class="col-xl-6">
        <section class="content-card h-100">
            <div class="content-card-header"><div><h2>Revenue by Company</h2><p>Top companies in the current filter set.</p></div></div>
            <div class="dashboard-chart-wrapper chart-compact"><canvas id="revenueCompanyChart" data-chart="revenueCompany" role="img" aria-label="Revenue by company chart"></canvas></div>
        </section>
    </div>
    <div class="col-xl-6">
        <section class="content-card h-100">
            <div class="content-card-header"><div><h2>Revenue by Subscription Plan</h2><p>Recognized revenue grouped by plan.</p></div></div>
            <div class="dashboard-chart-wrapper chart-compact"><canvas id="revenuePlanChart" data-chart="revenuePlan" role="img" aria-label="Revenue by subscription plan chart"></canvas></div>
        </section>
    </div>
    <div class="col-xl-6">
        <section class="content-card h-100">
            <div class="content-card-header"><div><h2>Payment Status Distribution</h2><p>All platform subscription payments in the current filter set.</p></div></div>
            <div class="dashboard-pie-wrapper chart-medium"><canvas id="revenueStatusChart" data-chart="revenueStatus" role="img" aria-label="Payment status distribution chart"></canvas></div>
        </section>
    </div>
</div>

<section class="content-card">
    <div class="content-card-header">
        <div>
            <h2>Subscription Payment Transactions</h2>
            <p>{{ $payments->total() }} records found. Only verified, received and paid statuses count as recognized revenue.</p>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Transaction Reference</th>
                    <th>Company</th>
                    <th>Subscription Plan</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Payment Status</th>
                    <th>Payment Date</th>
                    <th>Verified By</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                    <tr>
                        <td>{{ $payment->transaction_reference ?? 'Payment #'.$payment->id }}</td>
                        <td>{{ $payment->company?->name ?? '-' }}</td>
                        <td>{{ $payment->subscriptionPlan?->name ?? '-' }}</td>
                        <td>{{ $currency }} {{ number_format((float) $payment->amount, 2) }}</td>
                        <td>{{ str_replace('_', ' ', $payment->method) }}</td>
                        <td>@include('partials.status-badge', ['status' => $payment->status])</td>
                        <td>{{ ($payment->verified_at ?? $payment->paid_at ?? $payment->created_at)?->format('M d, Y') }}</td>
                        <td>{{ $payment->verifier?->name ?? '-' }}</td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('super-admin.payments.show', $payment) }}"><i class="fa-regular fa-eye"></i> View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="empty-cell">
                            @include('partials.empty-state', ['icon' => 'fa-sack-dollar', 'title' => 'No revenue records', 'message' => 'No subscription payments match the selected filters.'])
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $payments->links() }}
</section>
@endsection

@push('scripts')
<script>
    window.elevanixRevenueCharts = @json($chartData);
</script>
@endpush
