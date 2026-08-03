@extends('layouts.app')

@section('title', 'Work Sessions - Elevanix')

@section('content')
@include('partials.page-header', [
    'eyebrow' => 'Time Tracking',
    'title' => 'Work Sessions',
    'description' => 'Monitor employee timers, daily hours and monthly work totals.',
    'actions' => new \Illuminate\Support\HtmlString('<a class="btn btn-outline-primary" href="'.route('company-admin.work-sessions.export', request()->query()).'"><i class="fa-solid fa-file-csv"></i>Export CSV</a><a class="btn btn-primary" href="'.route('company-admin.work-sessions.pdf', request()->query()).'"><i class="fa-solid fa-file-pdf"></i>Export PDF</a>')
])
<div class="stat-grid">
    @include('partials.stat-card', ['label' => 'Running Timers', 'value' => $runningTimers, 'icon' => 'fa-play'])
    @include('partials.stat-card', ['label' => 'Today Hours', 'value' => number_format($todayMinutes / 60, 1), 'icon' => 'fa-clock', 'tone' => 'blue'])
    @include('partials.stat-card', ['label' => 'Month Hours', 'value' => number_format($monthMinutes / 60, 1), 'icon' => 'fa-calendar-days', 'tone' => 'green'])
</div>
<section class="content-card">
    <form class="row g-2 mb-3">
        <div class="col-md-3"><select class="form-select" name="employee_id"><option value="">All employees</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected(request('employee_id')==$employee->id)>{{ $employee->name }}</option>@endforeach</select></div>
        <div class="col-md-3"><select class="form-select" name="project_id"><option value="">All projects</option>@foreach($projects as $project)<option value="{{ $project->id }}" @selected(request('project_id')==$project->id)>{{ $project->name }}</option>@endforeach</select></div>
        <div class="col-md-2"><input class="form-control" type="date" name="date_from" value="{{ request('date_from') }}"></div>
        <div class="col-md-2"><input class="form-control" type="date" name="date_to" value="{{ request('date_to') }}"></div>
        <div class="col-md-2"><button class="btn btn-outline-primary w-100" type="submit"><i class="fa-solid fa-filter"></i>Filter</button></div>
    </form>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Employee</th><th>Project</th><th>Task</th><th>Started</th><th>Ended</th><th>Hours</th><th>Status</th><th>Correction</th></tr></thead>
            <tbody>
                @forelse($sessions as $session)
                    <tr>
                        <td>{{ $session->user?->name }}</td>
                        <td>{{ $session->project?->name ?? '-' }}</td>
                        <td>{{ $session->task?->title ?? '-' }}</td>
                        <td>{{ $session->started_at?->format('Y-m-d H:i') }}</td>
                        <td>{{ $session->ended_at?->format('Y-m-d H:i') ?? 'Running' }}</td>
                        <td>{{ number_format($session->duration_minutes / 60, 1) }}</td>
                        <td>@include('partials.status-badge', ['status' => $session->status ?? ($session->ended_at ? 'stopped' : 'running')])</td>
                        <td>
                            @if($session->ended_at)
                                <form method="POST" action="{{ route('company-admin.work-sessions.update', $session) }}" class="row g-1 align-items-center">
                                    @csrf @method('PATCH')
                                    <div class="col-4"><input class="form-control form-control-sm" type="number" name="duration_minutes" value="{{ $session->duration_minutes }}" min="1"></div>
                                    <div class="col-4"><input class="form-control form-control-sm" name="adjustment_reason" placeholder="Reason" required></div>
                                    <div class="col-4"><button class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-check"></i>Save</button></div>
                                </form>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="empty-cell">@include('partials.empty-state', ['icon' => 'fa-clock', 'title' => 'No work sessions', 'message' => 'Employee tracked time appears here.'])</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $sessions->links() }}
</section>
@endsection
