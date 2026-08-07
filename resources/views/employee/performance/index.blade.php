@extends('layouts.app')

@section('title', 'My Work Summary - Elevanix')

@section('content')
@include('partials.page-header', ['eyebrow' => 'Employee Reports', 'title' => 'My Work Summary', 'description' => 'Your tasks, logged hours, attendance and leave history for the selected period.'])

<section class="content-card mb-3">
    <form class="row g-2 align-items-end">
        <div class="col-md-4"><label class="form-label" for="date_from">From</label><input class="form-control" id="date_from" type="date" name="date_from" value="{{ request('date_from', $from->format('Y-m-d')) }}"></div>
        <div class="col-md-4"><label class="form-label" for="date_to">To</label><input class="form-control" id="date_to" type="date" name="date_to" value="{{ request('date_to', $to->format('Y-m-d')) }}"></div>
        <div class="col-md-2"><button class="btn btn-outline-primary" type="submit"><i class="fa-solid fa-filter"></i>Filter</button></div>
    </form>
</section>

<div class="stat-grid mb-3">
    @foreach($summary as $card)
        @include('partials.stat-card', $card)
    @endforeach
</div>

<div class="content-grid mb-3">
    <section class="content-card">
        <div class="content-card-header"><div><h2>Task Distribution</h2><p>@foreach($chartData['taskLabels'] as $index => $label){{ $label }}: {{ $chartData['taskValues'][$index] }}@if(! $loop->last), @endif @endforeach</p></div></div>
        <div class="dashboard-pie-wrapper chart-medium"><canvas id="employeePersonalTaskChart" data-chart="employeePersonalTasks" role="img" aria-label="Personal task distribution chart"></canvas></div>
    </section>
    <section class="content-card">
        <div class="content-card-header"><div><h2>Logged Hours</h2><p>Work-session hours grouped by day for the selected period.</p></div></div>
        <div class="dashboard-chart-wrapper chart-small"><canvas id="employeePersonalHoursChart" data-chart="employeePersonalHours" role="img" aria-label="Personal logged hours chart"></canvas></div>
    </section>
</div>

<div class="content-grid mb-3">
    <section class="content-card">
        <div class="content-card-header"><div><h2>Tasks</h2><p>Recent assigned tasks.</p></div></div>
        @include('employee.tasks._table', ['tasks' => $taskRows])
    </section>
    <section class="content-card">
        <div class="content-card-header"><div><h2>Work Sessions</h2><p>Recent logged work.</p></div></div>
        <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Date</th><th>Project</th><th>Task</th><th>Duration</th><th>Remarks</th></tr></thead><tbody>@forelse($sessions as $session)<tr><td>{{ $session->started_at?->format('Y-m-d') }}</td><td>{{ $session->project?->name ?? '-' }}</td><td>{{ $session->task?->title ?? '-' }}</td><td>{{ number_format($session->duration_minutes / 60, 1) }}</td><td>{{ $session->notes ?? '-' }}</td></tr>@empty<tr><td colspan="5" class="empty-cell">No work sessions found.</td></tr>@endforelse</tbody></table></div>
    </section>
</div>

<div class="content-grid">
    <section class="content-card">
        <div class="content-card-header"><div><h2>Attendance</h2><p>Recent attendance records.</p></div></div>
        <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Date</th><th>Check In</th><th>Check Out</th><th>Net Hours</th><th>Status</th></tr></thead><tbody>@forelse($attendances as $attendance)<tr><td>{{ $attendance->attendance_date?->format('Y-m-d') }}</td><td>{{ $attendance->check_in_time?->format('H:i') ?? '-' }}</td><td>{{ $attendance->check_out_time?->format('H:i') ?? '-' }}</td><td>{{ number_format($attendance->net_work_minutes / 60, 1) }}</td><td>@include('partials.status-badge', ['status' => $attendance->status])</td></tr>@empty<tr><td colspan="5" class="empty-cell">No attendance records found.</td></tr>@endforelse</tbody></table></div>
    </section>
    <section class="content-card">
        <div class="content-card-header"><div><h2>Leave</h2><p>Recent leave requests.</p></div></div>
        <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Type</th><th>Start</th><th>End</th><th>Duration</th><th>Status</th></tr></thead><tbody>@forelse($leaves as $leave)<tr><td>{{ ucfirst($leave->leave_type) }}</td><td>{{ $leave->start_date->format('Y-m-d') }}</td><td>{{ $leave->end_date->format('Y-m-d') }}</td><td>{{ $leave->total_days }}</td><td>@include('partials.status-badge', ['status' => $leave->status])</td></tr>@empty<tr><td colspan="5" class="empty-cell">No leave requests found.</td></tr>@endforelse</tbody></table></div>
    </section>
</div>

@push('scripts')
<script>window.elevanixEmployeePersonalReports = @json($chartData);</script>
@endpush
@endsection
