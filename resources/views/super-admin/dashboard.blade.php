@extends('layouts.app')

@section('title', 'Super Admin Dashboard - Elevanix')

@section('content')
<div class="page-header">
    <div>
        <span>Super Admin</span>
        <h1>Platform Dashboard</h1>
    </div>
    <a class="btn btn-primary" href="{{ route('super-admin.company-requests.index') }}"><i class="fa-solid fa-building-circle-check"></i> Review Requests</a>
</div>

<div class="stat-grid">
    @include('partials.stat-card', ['label' => 'Companies', 'value' => $companiesCount, 'icon' => 'fa-building'])
    @include('partials.stat-card', ['label' => 'Pending Requests', 'value' => $pendingRequestsCount, 'icon' => 'fa-clock', 'tone' => 'yellow'])
    @include('partials.stat-card', ['label' => 'Employees', 'value' => $employeesCount, 'icon' => 'fa-users', 'tone' => 'blue'])
    @include('partials.stat-card', ['label' => 'Projects', 'value' => $projectsCount, 'icon' => 'fa-diagram-project', 'tone' => 'green'])
</div>

<div class="content-grid">
    <section class="content-card">
        <div class="section-title"><h2>Latest Company Requests</h2></div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Company</th><th>Admin</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse($latestRequests as $request)
                        <tr>
                            <td>{{ $request->company_name }}<small>{{ $request->company_email }}</small></td>
                            <td>{{ $request->admin_name }}</td>
                            <td>@include('partials.status-badge', ['status' => $request->status])</td>
                            <td><a href="{{ route('super-admin.company-requests.show', $request) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty-cell">No registration requests yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    <section class="content-card">
        <div class="section-title"><h2>Recent Audit Logs</h2></div>
        <div class="activity-list">
            @forelse($latestAuditLogs as $log)
                <div>
                    <strong>{{ str_replace('_', ' ', $log->action) }}</strong>
                    <span>{{ $log->description }}</span>
                </div>
            @empty
                <p class="empty-cell">No audit activity yet.</p>
            @endforelse
        </div>
    </section>
</div>
@endsection
