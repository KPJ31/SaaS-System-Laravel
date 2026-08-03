@extends('layouts.app')

@section('title', 'Reports - Elevanix')

@section('content')
@include('partials.page-header', ['eyebrow' => 'Analytics', 'title' => 'Reports and Analytics', 'description' => 'Simple company-scoped reporting for employees, projects, tasks, revenue and feedback.'])
<section class="content-card mb-3"><form class="row g-2"><div class="col-md-4"><input class="form-control" type="date" name="date_from" value="{{ request('date_from', $from->format('Y-m-d')) }}"></div><div class="col-md-4"><input class="form-control" type="date" name="date_to" value="{{ request('date_to', $to->format('Y-m-d')) }}"></div><div class="col-md-2"><button class="btn btn-outline-primary" type="submit">Filter</button></div></form></section>
<div class="stat-grid">
    @include('partials.stat-card', ['label' => 'Employees', 'value' => $employeeCount, 'icon' => 'fa-users'])
    @include('partials.stat-card', ['label' => 'Clients', 'value' => $clientCount, 'icon' => 'fa-handshake', 'tone' => 'blue'])
    @include('partials.stat-card', ['label' => 'Projects', 'value' => $projectCount, 'icon' => 'fa-diagram-project', 'tone' => 'green'])
    @include('partials.stat-card', ['label' => 'Revenue', 'value' => '$'.number_format($revenue, 2), 'icon' => 'fa-money-bill-trend-up', 'tone' => 'green'])
</div>
@php
    $reports = [
        'employees' => ['Employee Report', 'Employee account and status details.', 'fa-users'],
        'employee-performance' => ['Employee Performance Report', 'Completed tasks, work hours and simple performance rate.', 'fa-chart-line'],
        'projects' => ['Project Report', 'Project status, priority, budget and due dates.', 'fa-diagram-project'],
        'project-progress' => ['Project Progress Report', 'Progress percentage by project.', 'fa-bars-progress'],
        'tasks' => ['Task Report', 'Task assignments, priorities and status.', 'fa-list-check'],
        'overdue-tasks' => ['Overdue Task Report', 'Tasks past due and not completed.', 'fa-triangle-exclamation'],
        'work-hours' => ['Work Hour Report', 'Employee work sessions and tracked hours.', 'fa-clock'],
        'clients' => ['Client Report', 'Client contact and organization details.', 'fa-handshake'],
        'project-requests' => ['Project Request Report', 'Client request status and budgets.', 'fa-inbox'],
        'payments' => ['Payment Report', 'Client project payment records.', 'fa-credit-card'],
        'invoices' => ['Invoice Report', 'Invoice totals and payment status.', 'fa-file-invoice-dollar'],
        'revenue' => ['Revenue Report', 'Client project revenue records.', 'fa-money-bill-trend-up'],
        'leave' => ['Leave Report', 'Employee leave requests and review status.', 'fa-calendar-check'],
        'attendance' => ['Attendance Report', 'Employee attendance, late arrivals and early departures.', 'fa-calendar-days'],
        'activity-logs' => ['Activity Log Report', 'Company activity records and tracked actions.', 'fa-clipboard-list'],
        'feedback' => ['Feedback Report', 'Client ratings and feedback status.', 'fa-star'],
    ];
@endphp
@php
    $reportRoutePrefix = auth()->user()->role === 'employee' ? 'employee.reports' : 'company-admin.reports';
@endphp

<div class="content-grid">
    <section class="content-card">
        <div class="content-card-header">
            <div>
                <h2>Available Reports</h2>
                <p>Open a report first, or download it directly as CSV or PDF.</p>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Report</th>
                        <th>Description</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reports as $key => [$label, $description, $icon])
                        @if(auth()->user()->role === 'employee')
                            @php
                                $requiredPermission = [
                                    'employees' => 'employees.view',
                                    'employee-performance' => 'employees.view',
                                    'projects' => 'projects.view',
                                    'project-progress' => 'projects.view',
                                    'project-requests' => 'project-requests.view',
                                    'tasks' => 'tasks.view',
                                    'overdue-tasks' => 'tasks.view',
                                    'work-hours' => 'work-sessions.view-all',
                                    'clients' => 'clients.view',
                                    'payments' => 'payments.view',
                                    'revenue' => 'payments.view',
                                    'invoices' => 'invoices.view',
                                    'feedback' => 'feedback.view',
                                    'leave' => 'leave-requests.view-all',
                                    'attendance' => 'attendance.view-all',
                                    'activity-logs' => 'activity-logs.view',
                                ][$key] ?? null;
                            @endphp
                            @continue($requiredPermission && ! auth()->user()->can($requiredPermission))
                        @endif
                        <tr>
                            <td><i class="fa-solid {{ $icon }} me-2 text-primary"></i>{{ $label }}</td>
                            <td>{{ $description }}</td>
                            <td>
                                <div class="table-actions">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route($reportRoutePrefix.'.show', array_merge(['report' => $key], request()->query())) }}"><i class="fa-regular fa-eye"></i>View</a>
                                    @can('reports.export')
                                        <a class="btn btn-sm btn-outline-primary" href="{{ route($reportRoutePrefix.'.export', array_merge(['report' => $key], request()->query())) }}"><i class="fa-solid fa-file-csv"></i>CSV</a>
                                        <a class="btn btn-sm btn-primary" href="{{ route($reportRoutePrefix.'.pdf', array_merge(['report' => $key], request()->query())) }}"><i class="fa-solid fa-file-pdf"></i>PDF</a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
    <section class="content-card"><h2>Totals</h2><dl class="detail-list mt-3"><dt>Tasks</dt><dd>{{ $taskCount }}</dd><dt>Work Hours</dt><dd>{{ number_format($workMinutes / 60, 1) }}</dd><dt>Invoices</dt><dd>${{ number_format($invoiceTotal, 2) }}</dd><dt>Requests</dt><dd>{{ $requestCount }}</dd><dt>Feedback</dt><dd>{{ $feedbackCount }}</dd></dl></section>
</div>
@endsection
