@extends('layouts.app')

@section('title', 'Attendance Overview - Elevanix')

@section('content')
@include('partials.page-header', [
    'eyebrow' => 'Time Management',
    'title' => 'Attendance Overview',
    'description' => 'Review company attendance, late arrivals, half days, absences and corrections.',
    'actions' => new \Illuminate\Support\HtmlString('<a class="btn btn-outline-primary" href="'.route('company-admin.attendance.export', request()->query()).'"><i class="fa-solid fa-file-export"></i>Export CSV</a><a class="btn btn-primary" href="'.route('company-admin.attendance.pdf', request()->query()).'"><i class="fa-solid fa-file-pdf"></i>Export PDF</a>')
])

<div class="stat-grid">
    @foreach([
        ['Employees', $stats['employees'], 'fa-users'],
        ['Present Today', $stats['present'], 'fa-user-check', 'green'],
        ['Late Today', $stats['late'], 'fa-clock-rotate-left', 'yellow'],
        ['Half Day', $stats['half_day'], 'fa-circle-half-stroke', 'blue'],
        ['Absent Today', $stats['absent'], 'fa-user-xmark', 'yellow'],
        ['On Leave', $stats['on_leave'], 'fa-calendar-check', 'blue'],
        ['Not Checked In', $stats['not_checked_in'], 'fa-user-clock', 'yellow'],
        ['Currently Working', $stats['working'], 'fa-stopwatch', 'green'],
    ] as $item)
        @include('partials.stat-card', ['label' => $item[0], 'value' => $item[1], 'icon' => $item[2], 'tone' => $item[3] ?? null])
    @endforeach
</div>

<section class="content-card mb-3">
    <form class="row g-3 align-items-end" method="GET" action="{{ route('company-admin.attendance.index') }}">
        <div class="col-md-3"><label class="form-label">Date</label><input class="form-control" type="date" name="date" value="{{ request('date', $date) }}"></div>
        <div class="col-md-3"><label class="form-label">Employee</label><select class="form-select" name="employee_id"><option value="">All Employees</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected((string) request('employee_id')===(string) $employee->id)>{{ $employee->name }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label">Status</label><select class="form-select" name="status"><option value="">All</option>@foreach(['present','late','half_day','absent','on_leave'] as $status)<option value="{{ $status }}" @selected(request('status')===$status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label">Search</label><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Employee name or email"></div>
        <div class="col-md-1"><button class="btn btn-primary w-100" type="submit"><i class="fa-solid fa-filter"></i></button></div>
    </form>
</section>

<section class="content-card">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Employee</th><th>Date</th><th>Check In</th><th>Check Out</th><th>Net</th><th>Status</th><th>Late</th><th>Early</th><th>Correction</th></tr></thead>
            <tbody>
                @forelse($attendances as $attendance)
                    <tr>
                        <td><strong>{{ $attendance->user?->name }}</strong><small>{{ $attendance->user?->job_title ?? 'Employee' }}</small></td>
                        <td>{{ $attendance->attendance_date?->format('Y-m-d') }}</td>
                        <td>{{ $attendance->check_in_time?->format('H:i') ?? '-' }}</td>
                        <td>{{ $attendance->check_out_time?->format('H:i') ?? '-' }}</td>
                        <td>{{ $attendance->net_work_minutes }} min</td>
                        <td>@include('partials.status-badge', ['status' => $attendance->status])</td>
                        <td>{{ $attendance->late_minutes ?: '-' }}</td>
                        <td>{{ $attendance->early_departure_minutes ?: '-' }}</td>
                        <td>
                            <form method="POST" action="{{ route('company-admin.attendance.update', $attendance) }}" class="d-grid gap-2">
                                @csrf @method('PATCH')
                                <input class="form-control form-control-sm" type="datetime-local" name="check_in_time" value="{{ $attendance->check_in_time?->format('Y-m-d\TH:i') }}" required>
                                <input class="form-control form-control-sm" type="datetime-local" name="check_out_time" value="{{ $attendance->check_out_time?->format('Y-m-d\TH:i') }}" required>
                                <input class="form-control form-control-sm" name="correction_reason" placeholder="Correction reason" required>
                                <button class="btn btn-sm btn-outline-primary" type="submit">Correct</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="empty-cell">No attendance records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $attendances->links() }}
</section>
@endsection
