@extends('layouts.app')

@section('title', 'My Attendance - Elevanix')

@section('content')
@include('partials.page-header', [
    'eyebrow' => 'Attendance',
    'title' => 'My Attendance',
    'description' => 'Check in, check out and review your attendance history separately from task work sessions.',
    'actions' => new \Illuminate\Support\HtmlString('<a class="btn btn-outline-primary" href="'.route('employee.attendance.export', request()->query()).'"><i class="fa-solid fa-file-csv"></i>Export CSV</a><a class="btn btn-primary" href="'.route('employee.attendance.pdf', request()->query()).'"><i class="fa-solid fa-file-pdf"></i>Export PDF</a>'),
])

<div class="stat-grid">
    @include('partials.stat-card', ['label' => 'Present Days', 'value' => $summary['present'], 'icon' => 'fa-user-check', 'tone' => 'green'])
    @include('partials.stat-card', ['label' => 'Late Days', 'value' => $summary['late'], 'icon' => 'fa-clock-rotate-left', 'tone' => 'yellow'])
    @include('partials.stat-card', ['label' => 'Half Days', 'value' => $summary['half_day'], 'icon' => 'fa-circle-half-stroke', 'tone' => 'blue'])
    @include('partials.stat-card', ['label' => 'Worked Hours', 'value' => round($summary['worked_minutes'] / 60, 2), 'icon' => 'fa-business-time', 'tone' => 'green'])
</div>

<section class="content-card mb-3">
    <div class="content-card-header">
        <div>
            <h2>Today</h2>
            <p>{{ now($settings['timezone'])->format('l, M d, Y H:i') }} {{ $settings['timezone'] }}</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @if(! $todayAttendance)
                <form method="POST" action="{{ route('employee.attendance.check-in') }}" data-loading-form>
                    @csrf
                    <button class="btn btn-primary" type="submit"><i class="fa-solid fa-right-to-bracket"></i>Check In</button>
                </form>
            @elseif(! $todayAttendance->check_out_time)
                <form method="POST" action="{{ route('employee.attendance.check-out') }}" class="d-flex gap-2" data-loading-form>
                    @csrf
                    <input class="form-control" name="note" placeholder="Checkout note">
                    <button class="btn btn-primary" type="submit"><i class="fa-solid fa-right-from-bracket"></i>Check Out</button>
                </form>
            @else
                @include('partials.status-badge', ['status' => $todayAttendance->status])
            @endif
        </div>
    </div>
    <dl class="detail-list mt-3">
        <dt>Work Window</dt><dd>{{ $settings['work_start_time'] }} - {{ $settings['work_end_time'] }}</dd>
        <dt>Check In</dt><dd>{{ $todayAttendance?->check_in_time?->format('H:i') ?? '-' }}</dd>
        <dt>Check Out</dt><dd>{{ $todayAttendance?->check_out_time?->format('H:i') ?? '-' }}</dd>
        <dt>Net Time</dt><dd>{{ $todayAttendance?->net_work_minutes ?? 0 }} min</dd>
    </dl>
</section>

<section class="content-card mb-3">
    <form class="row g-3 align-items-end" method="GET" action="{{ route('employee.attendance.index') }}">
        <div class="col-md-3"><label class="form-label">From</label><input class="form-control" type="date" name="date_from" value="{{ request('date_from') }}"></div>
        <div class="col-md-3"><label class="form-label">To</label><input class="form-control" type="date" name="date_to" value="{{ request('date_to') }}"></div>
        <div class="col-md-3"><label class="form-label">Status</label><select class="form-select" name="status"><option value="">All statuses</option>@foreach(['present','late','half_day','absent','on_leave'] as $status)<option value="{{ $status }}" @selected(request('status')===$status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>@endforeach</select></div>
        <div class="col-md-3"><button class="btn btn-primary w-100" type="submit"><i class="fa-solid fa-filter"></i>Filter</button></div>
    </form>
</section>

<section class="content-card">
    <div class="content-card-header"><div><h2>Attendance History</h2><p>Lunch is deducted when gross attendance is at least five hours.</p></div></div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Date</th><th>Day</th><th>Check In</th><th>Check Out</th><th>Gross</th><th>Lunch</th><th>Net</th><th>Status</th><th>Late</th><th>Early</th><th>Note</th></tr></thead>
            <tbody>
                @forelse($attendances as $attendance)
                    <tr>
                        <td>{{ $attendance->attendance_date?->format('Y-m-d') }}</td>
                        <td>{{ $attendance->attendance_date?->format('l') }}</td>
                        <td>{{ $attendance->check_in_time?->format('H:i') ?? '-' }}</td>
                        <td>{{ $attendance->check_out_time?->format('H:i') ?? '-' }}</td>
                        <td>{{ $attendance->gross_minutes }} min</td>
                        <td>{{ $attendance->lunch_break_minutes }} min</td>
                        <td>{{ $attendance->net_work_minutes }} min</td>
                        <td>@include('partials.status-badge', ['status' => $attendance->status])</td>
                        <td>{{ $attendance->late_minutes ?: '-' }}</td>
                        <td>{{ $attendance->early_departure_minutes ?: '-' }}</td>
                        <td>{{ $attendance->note ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="empty-cell">No attendance records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $attendances->links() }}
</section>
@endsection
