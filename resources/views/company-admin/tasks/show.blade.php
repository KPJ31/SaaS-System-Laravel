@extends('layouts.app')

@section('title', $task->title.' - Elevanix')

@section('content')
@include('partials.page-header', [
    'eyebrow' => 'Task Details',
    'title' => $task->title,
    'description' => $task->project?->name,
    'actions' => new \Illuminate\Support\HtmlString('<a class="btn btn-primary" href="'.route('company-admin.tasks.edit', $task).'"><i class="fa-solid fa-pen"></i>Edit</a>')
])

<div class="content-grid">
    <section class="content-card">
        <h2>Overview</h2>
        <dl class="detail-list mt-3">
            <dt>Status</dt><dd>@include('partials.status-badge', ['status' => $task->status])</dd>
            <dt>Assignee</dt><dd>{{ $task->assignee?->name ?? 'Unassigned' }}</dd>
            <dt>Priority</dt><dd><span class="priority-badge priority-{{ $task->priority }}">{{ ucfirst($task->priority) }}</span></dd>
            <dt>Type</dt><dd>{{ ucfirst($task->task_type ?? 'task') }}</dd>
            <dt>Due</dt><dd>{{ $task->due_date?->format('Y-m-d') ?? '-' }}</dd>
            <dt>Progress</dt><dd><div class="progress"><div class="progress-bar" style="width: {{ (int) $task->progress }}%"></div></div>{{ (int) $task->progress }}%</dd>
            <dt>Description</dt><dd>{{ $task->description ?? '-' }}</dd>
            @if($task->blocked_reason)<dt>Blocked Reason</dt><dd>{{ $task->blocked_reason }}</dd>@endif
        </dl>
    </section>

    <section class="content-card">
        <h2>Review Workflow</h2>
        <form method="POST" action="{{ route('company-admin.tasks.review', $task) }}" class="mt-3" data-loading-form>
            @csrf
            @method('PATCH')
            <label class="form-label">Review status</label>
            <select class="form-select mb-2" name="status">
                <option value="under_review">Under review</option>
                <option value="in_progress">Return for changes</option>
                <option value="completed">Approve and complete</option>
                <option value="cancelled">Cancel task</option>
            </select>
            <label class="form-label">Note for return/cancel</label>
            <textarea class="form-control mb-2" name="blocked_reason" rows="3">{{ old('blocked_reason') }}</textarea>
            <button class="btn btn-primary"><i class="fa-solid fa-check"></i>Update Review</button>
        </form>
    </section>
</div>

<div class="content-grid mt-3">
    <section class="content-card">
        <div class="content-card-header"><h2>Comments</h2></div>
        <form method="POST" action="{{ route('company-admin.tasks.comments.store', $task) }}" class="mb-3">
            @csrf
            <textarea class="form-control mb-2" name="comment" rows="3" required>{{ old('comment') }}</textarea>
            <button class="btn btn-outline-primary"><i class="fa-solid fa-comment"></i>Add Comment</button>
        </form>
        <div class="activity-list">
            @forelse($task->comments as $comment)
                <div><strong>{{ $comment->user?->name }}</strong><span>{{ $comment->comment }} | {{ $comment->created_at->diffForHumans() }}</span></div>
            @empty
                @include('partials.empty-state', ['icon' => 'fa-comments', 'title' => 'No comments', 'message' => 'Task discussion appears here.'])
            @endforelse
        </div>
    </section>

    <section class="content-card">
        <div class="content-card-header"><h2>Files</h2></div>
        <form method="POST" action="{{ route('company-admin.tasks.files.store', $task) }}" enctype="multipart/form-data" class="mb-3">
            @csrf
            <input class="form-control mb-2" type="file" name="file" required>
            <button class="btn btn-outline-primary"><i class="fa-solid fa-upload"></i>Upload File</button>
        </form>
        <div class="activity-list">
            @forelse($task->files as $file)
                <div><strong>{{ $file->original_name }}</strong><span>{{ $file->uploader?->name }} | <a href="{{ route('company-admin.files.download', $file) }}">Download</a></span></div>
            @empty
                @include('partials.empty-state', ['icon' => 'fa-folder-open', 'title' => 'No files', 'message' => 'Task files appear here.'])
            @endforelse
        </div>
    </section>
</div>

<section class="content-card mt-3">
    <div class="content-card-header"><h2>Work Sessions</h2></div>
    <div class="activity-list">
        @forelse($task->workSessions as $session)
            <div><strong>{{ $session->user?->name }}</strong><span>{{ $session->started_at?->format('Y-m-d H:i') }} - {{ $session->ended_at?->format('Y-m-d H:i') ?? 'Running' }} | {{ number_format($session->duration_minutes / 60, 1) }} hours</span></div>
        @empty
            @include('partials.empty-state', ['icon' => 'fa-clock', 'title' => 'No work sessions', 'message' => 'Employee tracked time appears here.'])
        @endforelse
    </div>
</section>
@endsection
