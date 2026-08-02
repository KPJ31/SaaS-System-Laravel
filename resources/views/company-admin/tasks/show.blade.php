@extends('layouts.app')

@section('title', $task->title.' - Elevanix')

@section('content')
@include('partials.page-header', ['eyebrow' => 'Task Details', 'title' => $task->title, 'description' => $task->project?->name, 'actions' => new \Illuminate\Support\HtmlString('<a class="btn btn-primary" href="'.route('company-admin.tasks.edit', $task).'"><i class="fa-solid fa-pen"></i>Edit</a>')])
<div class="content-grid">
    <section class="content-card"><h2>Overview</h2><dl class="detail-list mt-3"><dt>Status</dt><dd>@include('partials.status-badge', ['status' => $task->status])</dd><dt>Assignee</dt><dd>{{ $task->assignee?->name ?? 'Unassigned' }}</dd><dt>Priority</dt><dd>{{ ucfirst($task->priority) }}</dd><dt>Type</dt><dd>{{ ucfirst($task->task_type ?? 'task') }}</dd><dt>Due</dt><dd>{{ $task->due_date?->format('Y-m-d') ?? '-' }}</dd><dt>Description</dt><dd>{{ $task->description ?? '-' }}</dd></dl></section>
    <section class="content-card"><h2>Work Sessions</h2><div class="activity-list mt-3">@forelse($task->workSessions as $session)<div><strong>{{ $session->user?->name }}</strong><span>{{ $session->started_at?->format('Y-m-d H:i') }} - {{ number_format($session->duration_minutes / 60, 1) }} hours</span></div>@empty<div>No work sessions for this task.</div>@endforelse</div></section>
</div>
@endsection
