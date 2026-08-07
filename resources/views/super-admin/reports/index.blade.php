@extends('layouts.app')

@section('title', 'Reports and Analytics - Elevanix')

@section('content')
@include('partials.page-header', [
    'eyebrow' => 'Super Admin',
    'title' => 'Reports and Analytics',
    'description' => 'View each report first, then download it as CSV or PDF.',
])

<div class="stat-grid">
    @include('partials.stat-card', ['label' => 'Companies', 'value' => $companyCount, 'icon' => 'fa-building'])
    @include('partials.stat-card', ['label' => 'Active / Suspended', 'value' => $activeCompanyCount.' / '.$suspendedCompanyCount, 'icon' => 'fa-building-circle-check'])
    @include('partials.stat-card', ['label' => 'Subscriptions', 'value' => $subscriptionCount, 'icon' => 'fa-repeat'])
    @include('partials.stat-card', ['label' => 'Plan Changes', 'value' => $planChangeCount, 'icon' => 'fa-code-compare'])
    @include('partials.stat-card', ['label' => 'Pending Changes', 'value' => $pendingPlanChangeCount, 'icon' => 'fa-clock', 'tone' => 'yellow'])
    @include('partials.stat-card', ['label' => 'Revenue', 'value' => '$'.number_format($revenueTotal, 2), 'icon' => 'fa-sack-dollar', 'tone' => 'green'])
    @include('partials.stat-card', ['label' => 'Change Revenue', 'value' => '$'.number_format($planChangeRevenue, 2), 'icon' => 'fa-money-check-dollar', 'tone' => 'green'])
    @include('partials.stat-card', ['label' => 'Company Users', 'value' => $userCount, 'icon' => 'fa-users'])
    @include('partials.stat-card', ['label' => 'Expiring Soon', 'value' => $expiringSoonCount, 'icon' => 'fa-calendar-days', 'tone' => 'yellow'])
    @include('partials.stat-card', ['label' => 'Audit Logs', 'value' => $auditCount, 'icon' => 'fa-clipboard-list'])
</div>

@php
    $reportQuery = request()->except('report');
    $reports = [
        'companies' => ['Company Registration Report', 'Companies, emails, status and registration date.', 'fa-building'],
        'subscriptions' => ['Subscription Report', 'Company plans, status and subscription dates.', 'fa-repeat'],
        'payments' => ['Revenue Report', 'Subscription payment revenue and verification status.', 'fa-sack-dollar'],
        'users' => ['Company User Report', 'Platform users, role, company and account status.', 'fa-users'],
        'projects' => ['Project Monitoring Report', 'Platform projects by company, status and due date.', 'fa-diagram-project'],
        'tasks' => ['Task Monitoring Report', 'Platform task status and assignment visibility.', 'fa-list-check'],
        'subscription-expiry' => ['Subscription Expiry Report', 'Subscriptions with renewal or expiry warnings.', 'fa-calendar-days'],
        'subscription-changes' => ['Plan Change Request Report', 'Upgrades, downgrades, statuses and plan-change revenue.', 'fa-code-compare'],
        'audit-logs' => ['Audit Activity Report', 'Recent administrative activity and tracked actions.', 'fa-clipboard-list'],
    ];
@endphp

<section class="content-card">
    <div class="content-card-header">
        <div>
            <h2>Available Reports</h2>
            <p>Open a report to check the data before downloading.</p>
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
                    <tr>
                        <td>
                            <i class="fa-solid {{ $icon }} me-2 text-primary" aria-hidden="true"></i>
                            {{ $label }}
                        </td>
                        <td>{{ $description }}</td>
                        <td>
                            <div class="table-actions">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('super-admin.reports.show', array_merge(['report' => $key], $reportQuery)) }}">
                                    <i class="fa-regular fa-eye" aria-hidden="true"></i> View
                                </a>
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('super-admin.reports.export', array_merge(['report' => $key], $reportQuery)) }}">
                                    <i class="fa-solid fa-file-csv" aria-hidden="true"></i> CSV
                                </a>
                                <a class="btn btn-sm btn-primary" href="{{ route('super-admin.reports.pdf', array_merge(['report' => $key], $reportQuery)) }}">
                                    <i class="fa-solid fa-file-pdf" aria-hidden="true"></i> PDF
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
@endsection
