@extends('layouts.app')

@section('title', 'Company Dashboard - Elevanix')

@section('content')
@include('partials.page-header', [
    'breadcrumbs' => [
        ['label' => 'Dashboard'],
    ],
    'eyebrow' => auth()->user()->company->name,
    'title' => 'Welcome back, '.auth()->user()->name.'.',
    'description' => 'Track today\'s team activity, client work, pending reviews and delivery risk across your company.',
])

<div class="stat-grid mb-3">
    @foreach($primaryKpis as $card)
        @include('partials.stat-card', $card)
    @endforeach
</div>

<div class="stat-grid mb-3">
    @foreach($secondaryKpis as $card)
        @include('partials.stat-card', $card)
    @endforeach
</div>

<div class="content-grid mb-3">
    <section class="content-card">
        <div class="content-card-header">
            <div>
                <h2>Finance Snapshot</h2>
                <p>Client invoices and project payment status for this company.</p>
            </div>
            <a class="btn btn-sm btn-outline-primary" href="{{ route('company-admin.invoices.index') }}">
                <i class="fa-solid fa-file-invoice-dollar"></i>Invoices
            </a>
        </div>
        <dl class="detail-list mt-3">
            <dt>Revenue This Month</dt><dd>{{ $financeSnapshot['currency'] }} {{ number_format($financeSnapshot['month_revenue'], 2) }}</dd>
            <dt>Unpaid Balance</dt><dd>{{ $financeSnapshot['currency'] }} {{ number_format($financeSnapshot['unpaid_balance'], 2) }}</dd>
            <dt>Overdue Invoices</dt><dd>{{ $financeSnapshot['overdue_invoices'] }}</dd>
            <dt>Pending Payment Proofs</dt><dd>{{ $financeSnapshot['pending_payment_proofs'] }}</dd>
        </dl>
    </section>

    <section class="content-card">
        <div class="content-card-header">
            <div>
                <h2>Recent Invoices</h2>
                <p>Latest invoice balances and statuses.</p>
            </div>
            <a class="btn btn-sm btn-outline-primary" href="{{ route('company-admin.payments.index') }}">
                <i class="fa-solid fa-money-check-dollar"></i>Payments
            </a>
        </div>
        <div class="activity-list">
            @forelse($financeSnapshot['recent_invoices'] as $invoice)
                <div>
                    <strong><a href="{{ route('company-admin.invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a></strong>
                    <span>{{ $invoice->client?->name ?? '-' }} &middot; {{ $financeSnapshot['currency'] }} {{ number_format($invoice->balance_amount, 2) }} balance @include('partials.status-badge', ['status' => $invoice->status])</span>
                </div>
            @empty
                @include('partials.empty-state', ['icon' => 'fa-file-invoice-dollar', 'title' => 'No invoices yet', 'message' => 'Recent invoices will appear here.'])
            @endforelse
        </div>
    </section>
</div>

<div class="content-grid mb-3">
    <section class="content-card">
        <div class="content-card-header">
            <div>
                <h2>Task Status</h2>
                <p>Grouped view of current company task flow.</p>
            </div>
        </div>
        <div class="dashboard-pie-wrapper chart-medium">
            <canvas id="companyAdminTaskStatusChart" data-chart="companyTaskStatus" role="img" aria-label="Company task status distribution chart"></canvas>
        </div>
    </section>

    <section class="content-card">
        <div class="content-card-header">
            <div>
                <h2>Today's Attendance</h2>
                <p>Attendance records for active employees today.</p>
            </div>
            <a class="btn btn-sm btn-outline-primary" href="{{ route('company-admin.attendance.index') }}">
                <i class="fa-solid fa-calendar-days"></i>Open
            </a>
        </div>
        <div class="activity-list">
            @foreach($attendance as $item)
                <div>
                    <strong>{{ $item['label'] }}</strong>
                    <span>
                        <span class="fw-semibold text-dark">{{ $item['value'] }}</span>
                        @include('partials.status-badge', ['status' => $item['status']])
                    </span>
                </div>
            @endforeach
        </div>
    </section>
</div>

<div class="content-grid mb-3">
    <section class="content-card">
        <div class="content-card-header">
            <div>
                <h2>Team Workload</h2>
                <p>Active task assignments across employees.</p>
            </div>
            <a class="btn btn-sm btn-outline-primary" href="{{ route('company-admin.employees.index') }}">
                <i class="fa-solid fa-users"></i>Employees
            </a>
        </div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Open</th>
                        <th>In Progress</th>
                        <th>Overdue</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($teamWorkload as $employee)
                        <tr>
                            <td>
                                <a class="fw-semibold" href="{{ route('company-admin.employees.show', $employee) }}">{{ $employee->name }}</a>
                                <small>{{ $employee->job_title ?? $employee->email }}</small>
                            </td>
                            <td>{{ $employee->open_tasks_count }}</td>
                            <td>{{ $employee->in_progress_tasks_count }}</td>
                            <td>{{ $employee->overdue_tasks_count }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="empty-cell">
                                @include('partials.empty-state', ['icon' => 'fa-users', 'title' => 'No active workload', 'message' => 'Open employee task assignments will appear here.'])
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="content-card">
        <div class="content-card-header">
            <div>
                <h2>Upcoming Deadlines</h2>
                <p>Tasks and projects due within the next 14 days.</p>
            </div>
        </div>
        <div class="activity-list">
            @forelse($upcomingDeadlines as $deadline)
                <div>
                    <strong>
                        <a href="{{ $deadline['url'] }}">{{ $deadline['title'] }}</a>
                    </strong>
                    <span>
                        {{ $deadline['type'] }}@if($deadline['context']) &middot; {{ $deadline['context'] }}@endif
                        &middot; {{ $deadline['assignee'] }}
                        &middot; {{ $deadline['due_date']->format('Y-m-d') }}
                        @include('partials.status-badge', ['status' => $deadline['status']])
                    </span>
                </div>
            @empty
                @include('partials.empty-state', ['icon' => 'fa-calendar-check', 'title' => 'No near deadlines', 'message' => 'Deadlines due in the next two weeks will appear here.'])
            @endforelse
        </div>
    </section>
</div>

<div class="content-grid mb-3">
    <section class="content-card">
        <div class="content-card-header">
            <div>
                <h2>Needs Attention</h2>
                <p>Operational items that may require review.</p>
            </div>
        </div>
        <div class="activity-list">
            @foreach($pendingActions as $pendingAction)
                <div>
                    <strong><i class="fa-solid {{ $pendingAction['icon'] }} me-1" aria-hidden="true"></i>{{ $pendingAction['label'] }}</strong>
                    <span>
                        <span class="fw-semibold text-dark">{{ $pendingAction['count'] }}</span>
                        <a href="{{ $pendingAction['url'] }}">Review</a>
                    </span>
                </div>
            @endforeach
        </div>
    </section>

    <section class="content-card">
        <div class="content-card-header">
            <div>
                <h2>Recent Activity</h2>
                <p>Latest important actions in this company workspace.</p>
            </div>
            <a class="btn btn-sm btn-outline-primary" href="{{ route('company-admin.activity-logs.index') }}">
                <i class="fa-solid fa-clipboard-list"></i>Activity
            </a>
        </div>
        <div class="activity-list">
            @forelse($recentActivity as $activity)
                <div>
                    <strong>{{ str((string) $activity->action)->replace('_', ' ')->headline() }}</strong>
                    <span>{{ $activity->description }} &middot; {{ $activity->user?->name ?? 'System' }} &middot; {{ $activity->created_at->diffForHumans() }}</span>
                </div>
            @empty
                @include('partials.empty-state', ['icon' => 'fa-clipboard-list', 'title' => 'No activity yet', 'message' => 'Company activity will appear here.'])
            @endforelse
        </div>
    </section>
</div>

<div class="content-grid mb-3">
    <section class="content-card">
        <div class="content-card-header">
            <div>
                <h2>Project Status</h2>
                <p>Current distribution of company projects.</p>
            </div>
        </div>
        <div class="dashboard-pie-wrapper chart-medium">
            <canvas id="companyAdminProjectStatusChart" data-chart="companyProjectStatus" role="img" aria-label="Company project status distribution chart"></canvas>
        </div>
    </section>

    <section class="content-card">
        <div class="content-card-header">
            <div>
                <h2>Employee Work Hours</h2>
                <p>Top logged employee hours this month.</p>
            </div>
            <span class="text-muted small">{{ $monthWorkHours }} total hours</span>
        </div>
        <div class="dashboard-chart-wrapper chart-horizontal">
            <canvas id="companyAdminEmployeeHoursChart" data-chart="companyEmployeeHours" role="img" aria-label="Employee work hours comparison chart"></canvas>
        </div>
    </section>
</div>

<div class="content-grid">
    <section class="content-card">
        <div class="content-card-header">
            <div>
                <h2>Recently Added Employees</h2>
                <p>Newest team members in this company.</p>
            </div>
            <a class="btn btn-sm btn-outline-primary" href="{{ route('company-admin.employees.create') }}">
                <i class="fa-solid fa-user-plus"></i>Add
            </a>
        </div>
        <div class="activity-list">
            @forelse($recentEmployees as $employee)
                <div>
                    <strong><a href="{{ route('company-admin.employees.show', $employee) }}">{{ $employee->name }}</a></strong>
                    <span>
                        {{ $employee->job_title ?? $employee->email }}
                        &middot; {{ $employee->join_date?->format('Y-m-d') ?? $employee->created_at->format('Y-m-d') }}
                        @include('partials.status-badge', ['status' => $employee->status])
                    </span>
                </div>
            @empty
                @include('partials.empty-state', ['icon' => 'fa-users', 'title' => 'No employees yet', 'message' => 'New employees will appear here.'])
            @endforelse
        </div>
    </section>

    <section class="content-card">
        <div class="content-card-header">
            <div>
                <h2>Recent Clients</h2>
                <p>Newest client records and project context.</p>
            </div>
            <a class="btn btn-sm btn-outline-primary" href="{{ route('company-admin.clients.create') }}">
                <i class="fa-solid fa-plus"></i>Add
            </a>
        </div>
        <div class="activity-list">
            @forelse($recentClients as $client)
                <div>
                    <strong><a href="{{ route('company-admin.clients.show', $client) }}">{{ $client->name }}</a></strong>
                    <span>
                        {{ $client->email ?? $client->company_name ?? 'No contact set' }}
                        &middot; {{ $client->projects_count }} projects
                        @include('partials.status-badge', ['status' => $client->status])
                    </span>
                </div>
            @empty
                @include('partials.empty-state', ['icon' => 'fa-handshake', 'title' => 'No clients yet', 'message' => 'Client records will appear here.'])
            @endforelse
        </div>
    </section>
</div>

@push('scripts')
<script>
    window.elevanixCompanyCharts = @json($chartData);
</script>
@endpush
@endsection
