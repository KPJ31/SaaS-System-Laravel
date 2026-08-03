@extends('layouts.app')
@section('title', $task->title.' - Elevanix')
@section('content')
@include('partials.page-header', ['eyebrow' => 'Task Details', 'title' => $task->title, 'description' => $task->project?->name])

@if($activeTimer && $activeTimer->task_id === $task->id)
    <section class="content-card timer-card mb-3" data-active-timer data-started-at="{{ $activeTimer->started_at->toIso8601String() }}">
        <div><strong>Timer running</strong><span>Started {{ $activeTimer->started_at->format('M d, H:i') }}</span></div>
        <div class="timer-actions"><strong data-timer-output>00:00:00</strong></div>
    </section>
@endif

<div class="content-grid">
    <section class="content-card">
        <div class="content-card-header"><h2>Overview</h2></div>
        <dl class="detail-list">
            <dt>Status</dt><dd>@include('partials.status-badge', ['status' => $task->status])</dd>
            <dt>Priority</dt><dd><span class="priority-badge priority-{{ $task->priority }}">{{ ucfirst($task->priority) }}</span></dd>
            <dt>Type</dt><dd>{{ ucfirst($task->task_type ?? 'task') }}</dd>
            <dt>Start date</dt><dd>{{ $task->start_date?->format('Y-m-d') ?? '-' }}</dd>
            <dt>Due date</dt><dd>{{ $task->due_date?->format('Y-m-d') ?? '-' }}</dd>
            <dt>Estimated hours</dt><dd>{{ $task->estimated_hours ?? '-' }}</dd>
            <dt>Actual hours</dt><dd>{{ round($task->workSessions->sum('duration_minutes') / 60, 2) }}</dd>
            <dt>Progress</dt><dd><div class="progress"><div class="progress-bar" style="width: {{ (int) $task->progress }}%"></div></div>{{ (int) $task->progress }}%</dd>
            <dt>Description</dt><dd>{{ $task->description ?? '-' }}</dd>
            @if($task->blocked_reason)<dt>Blocked reason</dt><dd>{{ $task->blocked_reason }}</dd>@endif
        </dl>
    </section>
    <section class="content-card">
        <div class="content-card-header"><h2>Actions</h2></div>
        <div class="d-flex flex-wrap gap-2 mb-3">
            @if(! $activeTimer && ! in_array($task->status, ['completed', 'cancelled']))
                <form method="POST" action="{{ route('employee.tasks.start', $task) }}" data-confirm="Start working on this task?" data-loading-form>@csrf<button class="btn btn-primary" type="submit" data-loading-text="Starting..."><i class="fa-solid fa-play"></i>Start Work</button></form>
            @elseif($activeTimer && $activeTimer->task_id !== $task->id)
                <div class="alert alert-warning w-100 mb-0">
                    You already have an active timer for another task: <strong>{{ $activeTimer->task?->title ?? 'General work' }}</strong>.
                    @if($activeTimer->task)
                        <a href="{{ route('employee.tasks.show', $activeTimer->task) }}">Open active task</a>
                    @endif
                </div>
                <button class="btn btn-outline-primary" type="button" disabled><i class="fa-solid fa-play"></i>Start Work</button>
            @endif
            @if($activeTimer && $activeTimer->task_id === $task->id)
                <form method="POST" action="{{ route('employee.tasks.stop', $task) }}" class="w-100" data-confirm="Stop the current work session?" data-loading-form>
                    @csrf
                    <label class="form-label">Work note</label>
                    <textarea class="form-control mb-2" name="notes" rows="2" placeholder="Short note about this session"></textarea>
                    <button class="btn btn-outline-danger" type="submit" data-loading-text="Stopping..."><i class="fa-solid fa-stop"></i>Stop Work</button>
                </form>
            @endif
        </div>
        <form method="POST" action="{{ route('employee.tasks.progress', $task) }}" class="mb-3">
            @csrf @method('PATCH')
            <label class="form-label">Update progress</label>
            <div class="input-group"><input class="form-control" type="number" min="0" max="100" name="progress" value="{{ old('progress', $task->progress) }}"><button class="btn btn-outline-primary"><i class="fa-solid fa-check"></i>Save</button></div>
        </form>
        <form method="POST" action="{{ route('employee.tasks.status', $task) }}">
            @csrf @method('PATCH')
            <label class="form-label">Change status</label>
            <select class="form-select mb-2" name="status"><option value="in_progress">In progress</option><option value="paused">Paused</option><option value="blocked">Blocked</option><option value="submitted">Submitted for review</option></select>
            <textarea class="form-control mb-2" name="blocked_reason" rows="2" placeholder="Blocked reason, if needed"></textarea>
            <button class="btn btn-outline-primary"><i class="fa-solid fa-arrow-right"></i>Update Status</button>
        </form>
    </section>
</div>

<div class="content-grid mt-3">
    <section class="content-card">
        <div class="content-card-header"><h2>Comments</h2></div>
        <form method="POST" action="{{ route('employee.tasks.comments.store', $task) }}" class="mb-3">@csrf<textarea class="form-control mb-2" name="comment" rows="3" required>{{ old('comment') }}</textarea><button class="btn btn-primary"><i class="fa-solid fa-comment"></i>Add Comment</button></form>
        <div class="activity-list">@forelse($task->comments as $comment)<div><strong>{{ $comment->user?->name }}</strong><span>{{ $comment->comment }} | {{ $comment->created_at->diffForHumans() }}</span></div>@empty @include('partials.empty-state', ['icon' => 'fa-comments', 'title' => 'No comments', 'message' => 'Task discussion appears here.']) @endforelse</div>
    </section>
    <section class="content-card">
        <div class="content-card-header"><h2>Files</h2></div>
        <form method="POST" action="{{ route('employee.tasks.files.store', $task) }}" enctype="multipart/form-data" class="mb-3">@csrf<input class="form-control mb-2" type="file" name="file" required><button class="btn btn-primary"><i class="fa-solid fa-upload"></i>Upload</button></form>
        <div class="activity-list">@forelse($task->files as $file)<div><strong>{{ $file->original_name }}</strong><span>{{ number_format($file->size / 1024, 1) }} KB | <a href="{{ route('employee.files.download', $file) }}">Download</a></span></div>@empty @include('partials.empty-state', ['icon' => 'fa-folder-open', 'title' => 'No files', 'message' => 'Uploaded work files appear here.']) @endforelse</div>
    </section>
</div>

<section class="content-card mt-3">
    <div class="content-card-header"><h2>Work Sessions</h2></div>
    <div class="activity-list">@forelse($task->workSessions as $session)<div><strong>{{ $session->started_at?->format('M d, Y H:i') }}</strong><span>{{ $session->ended_at?->format('H:i') ?? 'Running' }} | {{ $session->duration_minutes }} minutes | {{ $session->notes }}</span></div>@empty @include('partials.empty-state', ['icon' => 'fa-clock', 'title' => 'No sessions', 'message' => 'Tracked time for this task appears here.']) @endforelse</div>
</section>
@endsection
