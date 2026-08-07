@extends('layouts.app')

@section('title', 'Employee Dashboard - Elevanix')

@section('content')
@include('partials.page-header', [
    'eyebrow' => auth()->user()->company->name,
    'title' => 'My Workspace',
    'description' => 'Your projects, tasks, timer, leave, notifications and work summary in one place.',
    'actions' => new \Illuminate\Support\HtmlString('<a class="btn btn-primary" href="'.route('employee.tasks.index').'"><i class="fa-solid fa-list-check"></i>View tasks</a><a class="btn btn-outline-primary" href="'.route('employee.leave-requests.create').'"><i class="fa-solid fa-calendar-plus"></i>Request leave</a>')
])

@if($activeTimer)
    <section class="content-card timer-card mb-3" data-active-timer data-started-at="{{ $activeTimer->started_at->toIso8601String() }}">
        <div>
            <strong>{{ $activeTimer->task?->title ?? 'Running work session' }}</strong>
            <span>{{ $activeTimer->project?->name ?? 'General work' }} | Started {{ $activeTimer->started_at->format('M d, H:i') }}</span>
        </div>
        <div class="timer-actions">
            <strong data-timer-output>00:00:00</strong>
            @if($activeTimer->task)
                <form method="POST" action="{{ route('employee.tasks.stop', $activeTimer->task) }}">
                    @csrf
                    <input type="hidden" name="notes" value="Stopped from dashboard">
                    <button class="btn btn-outline-danger btn-sm" type="submit"><i class="fa-solid fa-stop"></i>Stop</button>
                </form>
            @endif
        </div>
    </section>
@endif

<section class="content-card mb-3">
    <div class="content-card-header">
        <div>
            <h2>Today Attendance</h2>
            <p>{{ now($attendanceSettings['timezone'])->format('l, M d, Y') }} | Expected {{ $attendanceSettings['work_start_time'] }} - {{ $attendanceSettings['work_end_time'] }} | Lunch {{ $attendanceSettings['lunch_break_minutes'] }} min</p>
        </div>
        <a class="btn btn-sm btn-outline-primary" href="{{ route('employee.attendance.index') }}"><i class="fa-solid fa-calendar-days"></i>History</a>
    </div>
    <div class="attendance-card-grid">
        <div>
            <span class="helper-text">Status</span>
            <div class="mt-1">@include('partials.status-badge', ['status' => $attendance?->status ?? 'not_checked_in'])</div>
        </div>
        <div><span class="helper-text">Check In</span><strong>{{ $attendance?->check_in_time?->format('H:i') ?? '-' }}</strong></div>
        <div><span class="helper-text">Check Out</span><strong>{{ $attendance?->check_out_time?->format('H:i') ?? '-' }}</strong></div>
        <div><span class="helper-text">Attendance Net</span><strong>{{ $attendance ? round($attendance->net_work_minutes / 60, 2).' h' : '0 h' }}</strong></div>
        <div><span class="helper-text">Task Work Today</span><strong>{{ round($attendanceSummary['todayTaskMinutes'] / 60, 2) }} h</strong></div>
        <div><span class="helper-text">Unallocated Time</span><strong>{{ round($attendanceSummary['unallocatedMinutes'] / 60, 2) }} h</strong></div>
    </div>
    @if($attendance?->is_late)
        <div class="alert alert-warning mt-3 mb-0">Late arrival recorded: {{ $attendance->late_minutes }} minutes.</div>
    @endif
    @if($attendance?->is_early_departure)
        <div class="alert alert-info mt-3 mb-0">Early departure recorded: {{ $attendance->early_departure_minutes }} minutes.</div>
    @endif
    <div class="d-flex flex-wrap gap-2 mt-3">
        @if(! $attendance)
            <form method="POST" action="{{ route('employee.attendance.check-in') }}" data-confirm="Check in for today?">@csrf<button class="btn btn-primary" type="submit"><i class="fa-solid fa-right-to-bracket"></i>Check In</button></form>
        @elseif(! $attendance->check_out_time)
            <form method="POST" action="{{ route('employee.attendance.check-out') }}" data-confirm="Check out for today?">
                @csrf
                <input type="hidden" name="note" value="Checked out from dashboard">
                <button class="btn btn-outline-danger" type="submit"><i class="fa-solid fa-right-from-bracket"></i>Check Out</button>
            </form>
        @else
            <span class="helper-text">Attendance is completed for today.</span>
        @endif
    </div>
</section>

<div class="stat-grid">
    @foreach([
        ['Projects', $stats['totalProjects'], 'fa-diagram-project'],
        ['Active Projects', $stats['activeProjects'], 'fa-bolt', 'blue'],
        ['Tasks', $stats['totalTasks'], 'fa-list-check'],
        ['Pending', $stats['pendingTasks'], 'fa-hourglass-half', 'yellow'],
        ['In Progress', $stats['inProgressTasks'], 'fa-spinner', 'blue'],
        ['Submitted', $stats['submittedTasks'], 'fa-paper-plane'],
        ['Overdue', $stats['overdueTasks'], 'fa-triangle-exclamation', 'yellow'],
        ['Today Hours', $stats['todayHours'], 'fa-clock', 'green'],
        ['Week Hours', $stats['weekHours'], 'fa-calendar-week', 'green'],
        ['Month Hours', $stats['monthHours'], 'fa-calendar-days', 'green'],
        ['Pending Leave', $stats['pendingLeaves'], 'fa-calendar-check', 'yellow'],
        ['Task Completion', $stats['completionRate'] === null ? 'N/A' : $stats['completionRate'].'%', 'fa-chart-line', 'blue'],
    ] as $item)
        @include('partials.stat-card', ['label' => $item[0], 'value' => $item[1], 'icon' => $item[2], 'tone' => $item[3] ?? null])
    @endforeach
</div>

@if(auth()->user()->can('projects.view') || auth()->user()->can('employees.view') || auth()->user()->can('tasks.view') || auth()->user()->can('clients.view') || auth()->user()->can('payments.view') || auth()->user()->can('reports.view'))
    <div class="stat-grid">
        @can('projects.view')
            @include('partials.stat-card', ['label' => 'Company Projects', 'value' => $stats['companyTotalProjects'], 'icon' => 'fa-diagram-project'])
            @include('partials.stat-card', ['label' => 'Active Company Projects', 'value' => $stats['companyActiveProjects'], 'icon' => 'fa-bolt', 'tone' => 'blue'])
            @include('partials.stat-card', ['label' => 'Completed Company Projects', 'value' => $stats['companyCompletedProjects'], 'icon' => 'fa-circle-check', 'tone' => 'green'])
        @endcan
        @can('employees.view')
            @include('partials.stat-card', ['label' => 'Company Employees', 'value' => $stats['companyTotalEmployees'], 'icon' => 'fa-users'])
            @include('partials.stat-card', ['label' => 'Active Employees', 'value' => $stats['companyActiveEmployees'], 'icon' => 'fa-user-check', 'tone' => 'green'])
        @endcan
        @can('tasks.view')
            @include('partials.stat-card', ['label' => 'Company Tasks', 'value' => $stats['companyTotalTasks'], 'icon' => 'fa-list-check'])
            @include('partials.stat-card', ['label' => 'Pending Company Tasks', 'value' => $stats['companyPendingTasks'], 'icon' => 'fa-hourglass-half', 'tone' => 'yellow'])
            @include('partials.stat-card', ['label' => 'Company Overdue Tasks', 'value' => $stats['companyOverdueTasks'], 'icon' => 'fa-triangle-exclamation', 'tone' => 'yellow'])
        @endcan
        @can('clients.view')
            @include('partials.stat-card', ['label' => 'Company Clients', 'value' => $stats['companyClients'], 'icon' => 'fa-handshake'])
        @endcan
        @can('payments.view')
            @include('partials.stat-card', ['label' => 'Pending Payments', 'value' => $stats['pendingPayments'], 'icon' => 'fa-credit-card', 'tone' => 'yellow'])
            @include('partials.stat-card', ['label' => 'Paid Payments', 'value' => $stats['paidPayments'], 'icon' => 'fa-sack-dollar', 'tone' => 'green'])
        @endcan
        @can('reports.view')
            @include('partials.stat-card', ['label' => 'Reports', 'value' => 'Open', 'icon' => 'fa-chart-pie', 'tone' => 'blue'])
        @endcan
    </div>
@endif

<div class="content-grid">
    <section class="content-card">
        <div class="content-card-header"><div><h2>Assigned Tasks</h2><p>Recently assigned work.</p></div></div>
        @include('employee.tasks._table', ['tasks' => $tasks, 'compact' => true])
    </section>
    <section class="content-card">
        <div class="content-card-header"><div><h2>Charts</h2><p>Hours and task status.</p></div></div>
        <div class="dashboard-chart-wrapper chart-small">
            <canvas id="employeeWeeklyHoursChart" data-chart="employeeWeeklyHours" role="img" aria-label="Weekly tracked work hours chart"></canvas>
        </div>
        <hr>
        <div class="dashboard-pie-wrapper chart-small">
            <canvas id="employeeTaskStatusChart" data-chart="employeeTaskStatus" role="img" aria-label="Employee task status distribution chart"></canvas>
        </div>
    </section>
</div>

<div class="content-grid mt-3">
    <section class="content-card"><div class="content-card-header"><div><h2>Overdue Tasks</h2><p>Items needing attention.</p></div></div>@include('employee.tasks._table', ['tasks' => $overdueTasks, 'compact' => true])</section>
    <section class="content-card"><div class="content-card-header"><div><h2>Recent Work Sessions</h2></div></div><div class="activity-list">@forelse($sessions as $session)<div><strong>{{ $session->task?->title ?? 'General work' }}</strong><span>{{ $session->started_at?->format('M d, H:i') }} | {{ round($session->duration_minutes / 60, 2) }} hours</span></div>@empty @include('partials.empty-state', ['icon' => 'fa-clock', 'title' => 'No sessions', 'message' => 'Your tracked time appears here.']) @endforelse</div></section>
</div>

<div class="content-grid mt-3">
    <section class="content-card"><div class="content-card-header"><div><h2>Notifications</h2></div></div><div class="activity-list">@forelse($notifications as $notification)<div><strong>{{ $notification->data['title'] ?? class_basename($notification->type) }}</strong><span>{{ $notification->data['message'] ?? $notification->created_at->diffForHumans() }}</span></div>@empty @include('partials.empty-state', ['icon' => 'fa-bell', 'title' => 'No notifications', 'message' => 'New alerts appear here.']) @endforelse</div></section>
    <section class="content-card"><div class="content-card-header"><div><h2>Recent Activity</h2></div></div><div class="activity-list">@forelse($activities as $activity)<div><strong>{{ str_replace('_', ' ', $activity->action) }}</strong><span>{{ $activity->description }} | {{ $activity->created_at->diffForHumans() }}</span></div>@empty @include('partials.empty-state', ['icon' => 'fa-clipboard-list', 'title' => 'No activity', 'message' => 'Your activity appears here.']) @endforelse</div></section>
</div>
@endsection

@push('scripts')
<script>
window.elevanixEmployeeCharts = @json($chartData);
</script>
@endpush
