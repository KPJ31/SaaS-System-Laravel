@extends('layouts.app')

@section('title', 'Leave Requests - Elevanix')

@section('content')
@include('partials.page-header', ['eyebrow' => 'Company Admin', 'title' => 'Leave Requests', 'description' => 'Review employee leave requests for your company.'])

<div class="stat-grid">
    @include('partials.stat-card', ['label' => 'Pending', 'value' => $summary['pending'], 'icon' => 'fa-hourglass-half', 'tone' => 'yellow'])
    @include('partials.stat-card', ['label' => 'Approved', 'value' => $summary['approved'], 'icon' => 'fa-circle-check', 'tone' => 'green'])
    @include('partials.stat-card', ['label' => 'Rejected', 'value' => $summary['rejected'], 'icon' => 'fa-circle-xmark'])
    @include('partials.stat-card', ['label' => 'Approved Days', 'value' => $summary['days_approved'], 'icon' => 'fa-calendar-days', 'tone' => 'blue'])
</div>

<section class="content-card mb-3">
    <form class="row g-2">
        <div class="col-md-3"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Search employee"></div>
        <div class="col-md-3"><select class="form-select" name="employee_id"><option value="">All employees</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected(request('employee_id') == $employee->id)>{{ $employee->name }}</option>@endforeach</select></div>
        <div class="col-md-2"><select class="form-select" name="status"><option value="">All status</option>@foreach(['pending','approved','rejected','cancelled'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
        <div class="col-md-2"><select class="form-select" name="leave_type"><option value="">All types</option>@foreach(['annual','casual','sick','unpaid','emergency','other'] as $type)<option value="{{ $type }}" @selected(request('leave_type') === $type)>{{ ucfirst($type) }}</option>@endforeach</select></div>
        <div class="col-md-2"><button class="btn btn-primary w-100"><i class="fa-solid fa-filter"></i>Filter</button></div>
    </form>
</section>

<section class="content-card">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Employee</th><th>Type</th><th>Dates</th><th>Days</th><th>Status</th><th>Reviewer</th><th class="text-end">Action</th></tr></thead>
            <tbody>
                @forelse($leaves as $leave)
                    <tr>
                        <td>{{ $leave->user?->name }}</td>
                        <td>{{ ucfirst($leave->leave_type) }}</td>
                        <td>{{ $leave->start_date->format('M d') }} - {{ $leave->end_date->format('M d, Y') }}</td>
                        <td>{{ $leave->total_days }}</td>
                        <td>@include('partials.status-badge', ['status' => $leave->status])</td>
                        <td>{{ $leave->reviewer?->name ?? '-' }}</td>
                        <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('company-admin.leave-requests.show', $leave) }}"><i class="fa-solid fa-eye"></i>Review</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty-cell">@include('partials.empty-state', ['icon' => 'fa-calendar-check', 'title' => 'No leave requests', 'message' => 'Employee leave requests appear here.'])</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $leaves->links() }}
</section>
@endsection
