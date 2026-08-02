@extends('layouts.app')

@section('title', 'Employee Dashboard - Elevanix')

@section('content')
@include('partials.page-header', [
    'eyebrow' => auth()->user()->company->name,
    'title' => 'My Workspace',
    'description' => 'Your projects, tasks, timer, leave, notifications and performance in one place.',
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
        ['Performance', $stats['performanceScore'] === null ? 'N/A' : $stats['performanceScore'].'%', 'fa-chart-line', 'blue'],
    ] as $item)
        @include('partials.stat-card', ['label' => $item[0], 'value' => $item[1], 'icon' => $item[2], 'tone' => $item[3] ?? null])
    @endforeach
</div>

<div class="content-grid">
    <section class="content-card">
        <div class="content-card-header"><div><h2>Assigned Tasks</h2><p>Recently assigned work.</p></div></div>
        @include('employee.tasks._table', ['tasks' => $tasks, 'compact' => true])
    </section>
    <section class="content-card">
        <div class="content-card-header"><div><h2>Charts</h2><p>Hours and task status.</p></div></div>
        <canvas data-chart="employeeWeeklyHours" height="150"></canvas>
        <hr>
        <canvas data-chart="employeeTaskStatus" height="150"></canvas>
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
