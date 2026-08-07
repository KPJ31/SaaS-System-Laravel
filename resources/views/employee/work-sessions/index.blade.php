@extends('layouts.app')

@section('title', 'Work Sessions - Elevanix')

@section('content')
@include('partials.page-header', [
    'eyebrow' => 'Employee',
    'title' => 'My Work Sessions',
    'description' => 'Review timer history and submit manual work logs for admin review.',
    'actions' => new \Illuminate\Support\HtmlString('<a class="btn btn-outline-primary" href="'.route('employee.work-sessions.export', request()->query()).'"><i class="fa-solid fa-download"></i>Export CSV</a><a class="btn btn-primary" href="'.route('employee.work-sessions.pdf', request()->query()).'"><i class="fa-solid fa-file-pdf"></i>Export PDF</a>'),
])

<div class="stat-grid">
    @include('partials.stat-card', ['label' => 'Today Minutes', 'value' => $dailyTotal, 'icon' => 'fa-clock'])
    @include('partials.stat-card', ['label' => 'Week Minutes', 'value' => $weeklyTotal, 'icon' => 'fa-calendar-week', 'tone' => 'green'])
    @include('partials.stat-card', ['label' => 'Month Minutes', 'value' => $monthlyTotal, 'icon' => 'fa-calendar-days', 'tone' => 'blue'])
</div>

<section class="content-card mb-3">
    <div class="content-card-header"><div><h2>Manual Work Log</h2><p>Submitted manual entries are marked pending until an admin reviews or adjusts them.</p></div></div>
    <form method="POST" action="{{ route('employee.work-sessions.store') }}" class="row g-3 align-items-end" data-loading-form>
        @csrf
        <div class="col-md-3"><label class="form-label">Project</label><select class="form-select" name="project_id" required><option value="">Select project</option>@foreach($projects as $project)<option value="{{ $project->id }}" @selected(old('project_id') == $project->id)>{{ $project->name }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label">Task</label><select class="form-select" name="task_id"><option value="">No task</option>@foreach($tasks as $task)<option value="{{ $task->id }}" @selected(old('task_id') == $task->id)>{{ $task->title }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label">Start</label><input class="form-control" type="datetime-local" name="started_at" value="{{ old('started_at') }}" required></div>
        <div class="col-md-2"><label class="form-label">End</label><input class="form-control" type="datetime-local" name="ended_at" value="{{ old('ended_at') }}" required></div>
        <div class="col-md-2"><label class="form-label">Notes</label><input class="form-control" name="notes" value="{{ old('notes') }}"></div>
        <div class="col-12"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-plus"></i>Submit Manual Log</button></div>
    </form>
</section>

<section class="content-card mb-3">
    <form class="row g-2">
        <div class="col-md-3"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Search notes"></div>
        <div class="col-md-2"><select class="form-select" name="project_id"><option value="">All projects</option>@foreach($projects as $project)<option value="{{ $project->id }}" @selected(request('project_id') == $project->id)>{{ $project->name }}</option>@endforeach</select></div>
        <div class="col-md-2"><select class="form-select" name="task_id"><option value="">All tasks</option>@foreach($tasks as $task)<option value="{{ $task->id }}" @selected(request('task_id') == $task->id)>{{ $task->title }}</option>@endforeach</select></div>
        <div class="col-md-2"><select class="form-select" name="status"><option value="">All statuses</option>@foreach(['running','stopped','adjusted'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
        <div class="col-md-2"><input class="form-control" type="date" name="date" value="{{ request('date') }}"></div>
        <div class="col-md-1"><button class="btn btn-primary w-100"><i class="fa-solid fa-filter"></i></button></div>
    </form>
</section>

<section class="content-card">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Date</th><th>Project</th><th>Task</th><th>Start</th><th>Stop</th><th>Duration</th><th>Source</th><th>Status</th><th>Approval</th><th>Note</th></tr></thead>
            <tbody>
                @forelse($sessions as $session)
                    <tr>
                        <td>{{ $session->started_at?->format('M d, Y') }}</td>
                        <td>{{ $session->project?->name ?? '-' }}</td>
                        <td>{{ $session->task?->title ?? '-' }}</td>
                        <td>{{ $session->started_at?->format('H:i') }}</td>
                        <td>{{ $session->ended_at?->format('H:i') ?? 'Running' }}</td>
                        <td>{{ $session->duration_minutes }} min</td>
                        <td>{{ $session->is_manual ? 'Manual' : 'Timer' }}</td>
                        <td>@include('partials.status-badge', ['status' => $session->status ?? ($session->ended_at ? 'stopped' : 'running')])</td>
                        <td>{{ $session->approval_status ? ucfirst($session->approval_status) : '-' }}</td>
                        <td>{{ $session->notes ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="empty-cell">@include('partials.empty-state', ['icon' => 'fa-clock', 'title' => 'No work sessions', 'message' => 'Tracked time appears here.'])</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $sessions->links() }}
</section>
@endsection
