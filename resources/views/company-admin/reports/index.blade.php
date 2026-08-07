@extends('layouts.app')

@section('title', 'Reports - Elevanix')

@section('content')
@include('partials.page-header', ['eyebrow' => 'Analytics', 'title' => 'Reports and Analytics', 'description' => 'Company-scoped operational reports for projects, tasks, workforce activity and finance.'])

<section class="content-card mb-3">
    <form class="row g-2 align-items-end">
        <div class="col-md-4"><label class="form-label" for="date_from">From</label><input class="form-control" id="date_from" type="date" name="date_from" value="{{ request('date_from', $from->format('Y-m-d')) }}"></div>
        <div class="col-md-4"><label class="form-label" for="date_to">To</label><input class="form-control" id="date_to" type="date" name="date_to" value="{{ request('date_to', $to->format('Y-m-d')) }}"></div>
        <div class="col-md-2"><button class="btn btn-outline-primary" type="submit"><i class="fa-solid fa-filter"></i>Filter</button></div>
    </form>
</section>

<div class="stat-grid mb-3">
    @include('partials.stat-card', ['label' => 'Employees', 'value' => $employeeCount, 'icon' => 'fa-users'])
    @include('partials.stat-card', ['label' => 'Projects', 'value' => $projectCount, 'icon' => 'fa-diagram-project', 'tone' => 'green'])
    @include('partials.stat-card', ['label' => 'Revenue', 'value' => $currency.' '.number_format($revenue, 2), 'icon' => 'fa-money-bill-trend-up', 'tone' => 'green'])
    @include('partials.stat-card', ['label' => 'Open Balance', 'value' => $currency.' '.number_format($unpaidBalance, 2), 'icon' => 'fa-scale-balanced', 'tone' => 'yellow'])
</div>

@php
    $reportRoutePrefix = auth()->user()->role === 'employee' ? 'employee.reports' : 'company-admin.reports';
    $groupedReports = collect($definitions)->groupBy(fn ($definition) => $definition[3]);
@endphp

<div class="content-grid">
    @foreach($groupedReports as $category => $reports)
        <section class="content-card">
            <div class="content-card-header">
                <div>
                    <h2>{{ $category }}</h2>
                    <p>{{ $reports->count() }} report{{ $reports->count() === 1 ? '' : 's' }} available.</p>
                </div>
            </div>
            <div class="activity-list">
                @foreach($reports as $key => [$label, $description, $icon])
                    @if(in_array($key, ['employee-performance', 'project-progress'], true))
                        @continue
                    @endif
                    @if(auth()->user()->role === 'employee')
                        @php
                            $requiredPermission = [
                                'employees' => 'employees.view',
                                'employee-progress' => 'employees.view',
                                'projects' => 'projects.view',
                                'project-performance' => 'projects.view',
                                'project-requests' => 'project-requests.view',
                                'tasks' => 'tasks.view',
                                'task-performance' => 'tasks.view',
                                'overdue-tasks' => 'tasks.view',
                                'work-hours' => 'work-sessions.view-all',
                                'clients' => 'clients.view',
                                'payments' => 'payments.view',
                                'revenue' => 'payments.view',
                                'invoices' => 'invoices.view',
                                'financial-summary' => 'invoices.view',
                                'leave' => 'leave-requests.view-all',
                                'attendance' => 'attendance.view-all',
                                'activity-logs' => 'activity-logs.view',
                            ][$key] ?? null;
                        @endphp
                        @continue($requiredPermission && ! auth()->user()->can($requiredPermission))
                    @endif
                    <div>
                        <strong><i class="fa-solid {{ $icon }} me-1 text-primary"></i>{{ $label }}</strong>
                        <span>{{ $description }}</span>
                        <div class="table-actions">
                            <a class="btn btn-sm btn-outline-primary" href="{{ route($reportRoutePrefix.'.show', array_merge(['report' => $key], request()->query())) }}"><i class="fa-regular fa-eye"></i>View</a>
                            @can('reports.export')
                                <a class="btn btn-sm btn-outline-primary" href="{{ route($reportRoutePrefix.'.export', array_merge(['report' => $key], request()->query())) }}"><i class="fa-solid fa-file-csv"></i>Export CSV</a>
                                <a class="btn btn-sm btn-primary" href="{{ route($reportRoutePrefix.'.pdf', array_merge(['report' => $key], request()->query())) }}"><i class="fa-solid fa-file-pdf"></i>Export PDF</a>
                            @endcan
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach

    <section class="content-card">
        <h2>Period Totals</h2>
        <dl class="detail-list mt-3">
            <dt>Date Range</dt><dd>{{ $from->format('Y-m-d') }} to {{ $to->format('Y-m-d') }}</dd>
            <dt>Clients</dt><dd>{{ $clientCount }}</dd>
            <dt>Tasks</dt><dd>{{ $taskCount }}</dd>
            <dt>Work Hours</dt><dd>{{ number_format($workMinutes / 60, 1) }}</dd>
            <dt>Invoices</dt><dd>{{ $currency }} {{ number_format($invoiceTotal, 2) }}</dd>
        </dl>
    </section>
</div>
@endsection
