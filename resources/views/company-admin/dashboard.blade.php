@extends('layouts.app')

@section('title', 'Company Dashboard - Elevanix')

@section('content')
@include('partials.page-header', [
    'eyebrow' => auth()->user()->company->name,
    'title' => 'Welcome back, '.auth()->user()->name.'.',
    'description' => 'Review employees, clients, project delivery, payments, invoices and recent activity for your company.',
])

<div class="stat-grid">
    @include('partials.stat-card', ['label' => 'Clients', 'value' => $clientsCount, 'icon' => 'fa-handshake'])
    @include('partials.stat-card', ['label' => 'Employees', 'value' => $employeesCount, 'icon' => 'fa-users', 'tone' => 'blue'])
    @include('partials.stat-card', ['label' => 'With Extra Permissions', 'value' => $employeesWithPermissionsCount, 'icon' => 'fa-shield-halved', 'tone' => 'green'])
    @include('partials.stat-card', ['label' => 'No Extra Permissions', 'value' => $employeesWithoutPermissionsCount, 'icon' => 'fa-user-lock', 'tone' => 'yellow'])
    @include('partials.stat-card', ['label' => 'Projects', 'value' => $projectsCount, 'icon' => 'fa-diagram-project', 'tone' => 'green'])
    @include('partials.stat-card', ['label' => 'Tasks', 'value' => $tasksCount, 'icon' => 'fa-list-check', 'tone' => 'yellow'])
    @include('partials.stat-card', ['label' => 'Pending Requests', 'value' => $pendingRequestsCount, 'icon' => 'fa-inbox', 'tone' => 'yellow'])
    @include('partials.stat-card', ['label' => 'Pending Leave', 'value' => $pendingLeavesCount, 'icon' => 'fa-calendar-check', 'tone' => 'yellow'])
    @include('partials.stat-card', ['label' => 'Overdue Tasks', 'value' => $overdueTasksCount, 'icon' => 'fa-triangle-exclamation'])
    @include('partials.stat-card', ['label' => 'Today Hours', 'value' => number_format($todayWorkMinutes / 60, 1), 'icon' => 'fa-clock', 'tone' => 'blue'])
    @include('partials.stat-card', ['label' => 'Week Hours', 'value' => number_format($weekWorkMinutes / 60, 1), 'icon' => 'fa-calendar-week', 'tone' => 'blue'])
    @include('partials.stat-card', ['label' => 'Monthly Revenue', 'value' => '$'.number_format($monthlyRevenue, 2), 'icon' => 'fa-money-bill-trend-up', 'tone' => 'green'])
    @include('partials.stat-card', ['label' => 'Total Revenue', 'value' => '$'.number_format($totalRevenue, 2), 'icon' => 'fa-sack-dollar', 'tone' => 'green'])
</div>

<div class="content-card mb-3">
    <div class="page-header mb-0">
        <div class="page-header-copy">
            <span>Quick Actions</span>
            <h1>Common Workflows</h1>
        </div>
        <div class="page-header-actions">
            <a class="btn btn-primary" href="{{ route('company-admin.employees.create') }}"><i class="fa-solid fa-user-plus"></i>Add employee</a>
            <a class="btn btn-outline-primary" href="{{ route('company-admin.employees.permissions.index') }}"><i class="fa-solid fa-shield-halved"></i>Manage Employee Permissions</a>
            <a class="btn btn-outline-primary" href="{{ route('company-admin.clients.create') }}"><i class="fa-solid fa-handshake"></i>Add client</a>
            <a class="btn btn-outline-primary" href="{{ route('company-admin.projects.create') }}"><i class="fa-solid fa-diagram-project"></i>Create project</a>
            <a class="btn btn-outline-primary" href="{{ route('company-admin.tasks.create') }}"><i class="fa-solid fa-list-check"></i>Create task</a>
            <a class="btn btn-outline-primary" href="{{ route('company-admin.payments.create') }}"><i class="fa-solid fa-credit-card"></i>Payment request</a>
            <a class="btn btn-outline-primary" href="{{ route('company-admin.reports.index') }}"><i class="fa-solid fa-chart-pie"></i>Reports</a>
        </div>
    </div>
</div>

<div class="content-grid mb-3">
    <section class="content-card">
        <div class="content-card-header"><div><h2>Permission Modules</h2><p>Most assigned employee permission groups.</p></div></div>
        <div class="activity-list">
            @forelse($topPermissionModules as $module)
                <div><strong>{{ str_replace('-', ' ', ucfirst($module->module)) }}</strong><span>{{ $module->total }} assigned permissions</span></div>
            @empty
                @include('partials.empty-state', ['icon' => 'fa-shield-halved', 'title' => 'No extra permissions', 'message' => 'Assigned employee permissions will appear here.'])
            @endforelse
        </div>
    </section>
    <section class="content-card">
        <div class="content-card-header"><div><h2>Recent Permission Updates</h2><p>Latest changes made by company admins.</p></div></div>
        <div class="activity-list">
            @forelse($recentPermissionUpdates as $activity)
                <div><strong>{{ str_replace('_', ' ', $activity->action) }}</strong><span>{{ $activity->description }} | {{ $activity->created_at->diffForHumans() }}</span></div>
            @empty
                @include('partials.empty-state', ['icon' => 'fa-clipboard-list', 'title' => 'No permission updates', 'message' => 'Permission audit records will appear here.'])
            @endforelse
        </div>
    </section>
</div>

<div class="content-grid mb-3">
    <section class="content-card">
        <div class="content-card-header"><div><h2>Project Status</h2><p>Distribution by current project state.</p></div></div>
        <div class="dashboard-pie-wrapper chart-medium">
            <canvas id="companyAdminProjectStatusChart" data-chart="companyProjectStatus" role="img" aria-label="Company project status distribution chart"></canvas>
        </div>
    </section>
    <section class="content-card">
        <div class="content-card-header"><div><h2>Task Status</h2><p>Delivery load across task workflow stages.</p></div></div>
        <div class="dashboard-pie-wrapper chart-medium">
            <canvas id="companyAdminTaskStatusChart" data-chart="companyTaskStatus" role="img" aria-label="Company task status distribution chart"></canvas>
        </div>
    </section>
</div>
<div class="content-grid mb-3">
    <section class="content-card">
        <div class="content-card-header"><div><h2>Employee Work Hours</h2><p>This month by employee.</p></div></div>
        <div class="dashboard-chart-wrapper chart-horizontal">
            <canvas id="companyAdminEmployeeHoursChart" data-chart="companyEmployeeHours" role="img" aria-label="Employee work hours comparison chart"></canvas>
        </div>
    </section>
    <section class="content-card">
        <div class="content-card-header"><div><h2>Payment Status</h2><p>Client project payment distribution.</p></div></div>
        <div class="dashboard-pie-wrapper chart-medium">
            <canvas id="companyAdminPaymentStatusChart" data-chart="companyPaymentStatus" role="img" aria-label="Company payment status distribution chart"></canvas>
        </div>
    </section>
</div>

<div class="content-grid">
    <section class="content-card">
        <div class="content-card-header">
            <div>
                <h2>Recent Projects</h2>
                <p>Latest company projects with client context and progress.</p>
            </div>
        </div>
        <div class="project-list">
            @forelse($projects as $project)
                <article>
                    <strong>{{ $project->name }}</strong>
                    <span>{{ $project->client?->name ?? 'Internal' }}</span>
                    <div class="progress">
                        <div class="progress-bar" style="width: {{ $project->progress }}%"></div>
                    </div>
                </article>
            @empty
                @include('partials.empty-state', ['icon' => 'fa-diagram-project', 'title' => 'No projects yet', 'message' => 'Projects created for this company will appear here.'])
            @endforelse
        </div>
    </section>

    <section class="content-card">
        <div class="content-card-header">
            <div>
                <h2>Latest Tasks</h2>
                <p>Recently created tasks across active company projects.</p>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Task</th>
                        <th>Assignee</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tasks as $task)
                        <tr>
                            <td>{{ $task->title }}<small>{{ $task->project->name }}</small></td>
                            <td>{{ $task->assignee?->name ?? 'Unassigned' }}</td>
                            <td>@include('partials.status-badge', ['status' => $task->status])</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="empty-cell">@include('partials.empty-state', ['icon' => 'fa-list-check', 'title' => 'No tasks yet', 'message' => 'Assigned and unassigned tasks will appear here.'])</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

<div class="content-grid mt-3">
    <section class="content-card">
        <div class="content-card-header"><div><h2>Recent Leave Requests</h2><p>Latest employee leave activity.</p></div></div>
        <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Employee</th><th>Dates</th><th>Status</th></tr></thead><tbody>@forelse($leaveRequests as $leave)<tr><td>{{ $leave->user?->name }}</td><td>{{ $leave->start_date->format('M d') }} - {{ $leave->end_date->format('M d') }}</td><td>@include('partials.status-badge', ['status' => $leave->status])</td></tr>@empty<tr><td colspan="3" class="empty-cell">@include('partials.empty-state', ['icon' => 'fa-calendar-check', 'title' => 'No leave requests', 'message' => 'Leave requests will appear here.'])</td></tr>@endforelse</tbody></table></div>
    </section>
    <section class="content-card">
        <div class="content-card-header"><div><h2>Latest Activities</h2><p>Recent company audit records.</p></div></div>
        <div class="activity-list">@forelse($latestActivities as $activity)<div><strong>{{ str_replace('_', ' ', $activity->action) }}</strong><span>{{ $activity->description }}</span></div>@empty @include('partials.empty-state', ['icon' => 'fa-clipboard-list', 'title' => 'No activity', 'message' => 'Company activity appears here.']) @endforelse</div>
    </section>
</div>

<div class="content-grid mt-3">
    <section class="content-card">
        <div class="content-card-header"><div><h2>Recent Project Requests</h2><p>Newest requests waiting for review or conversion.</p></div></div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Request</th><th>Client</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($projectRequests as $requestItem)
                        <tr>
                            <td>{{ $requestItem->title }}<small>{{ $requestItem->service_type ?? 'General' }}</small></td>
                            <td>{{ $requestItem->client?->name ?? 'No client' }}</td>
                            <td>@include('partials.status-badge', ['status' => $requestItem->status])</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="empty-cell">No project requests yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    <section class="content-card">
        <div class="content-card-header"><div><h2>Recent Payments</h2><p>Latest client project payment records.</p></div></div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Client</th><th>Amount</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($payments as $payment)
                        <tr>
                            <td>{{ $payment->client?->name ?? 'Client payment' }}<small>{{ $payment->project?->name }}</small></td>
                            <td>${{ number_format($payment->amount, 2) }}</td>
                            <td>@include('partials.status-badge', ['status' => $payment->status])</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="empty-cell">No payments yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

@push('scripts')
<script>
    window.elevanixCompanyCharts = @json($chartData);
</script>
@endpush
@endsection
