@extends('layouts.app')

@section('title', $task->title.' - Elevanix')

@section('content')
@include('partials.page-header', [
    'eyebrow' => 'Task Workspace',
    'title' => $task->title,
    'description' => $task->project?->name,
])

@if($activeTimer && $activeTimer->task_id === $task->id)
    <section class="content-card timer-card mb-3" data-active-timer data-started-at="{{ $activeTimer->started_at->toIso8601String() }}">
        <div><strong>Timer running</strong><span>Started {{ $activeTimer->started_at->format('M d, H:i') }}</span></div>
        <div class="timer-actions"><strong data-timer-output>00:00:00</strong></div>
    </section>
@endif

<div class="stat-grid mb-3">
    @include('partials.stat-card', ['label' => 'Progress', 'value' => (int) $task->progress.'%', 'icon' => 'fa-bars-progress', 'tone' => 'green'])
    @include('partials.stat-card', ['label' => 'My Hours', 'value' => number_format($totalHours, 1), 'icon' => 'fa-clock', 'tone' => 'blue'])
    @include('partials.stat-card', ['label' => 'Comments', 'value' => $task->comments->count(), 'icon' => 'fa-comments'])
    @include('partials.stat-card', ['label' => 'Files', 'value' => $task->files->count(), 'icon' => 'fa-folder-open'])
</div>

<div class="content-grid mb-3">
    <section class="content-card">
        <div class="content-card-header"><div><h2>Overview</h2><p>Assignment, dates and current state.</p></div></div>
        <dl class="detail-list mt-3">
            <dt>Status</dt><dd>@include('partials.status-badge', ['status' => $task->status])</dd>
            <dt>Priority</dt><dd>@include('partials.priority-badge', ['priority' => $task->priority])</dd>
            <dt>Type</dt><dd>{{ ucfirst($task->task_type ?? 'task') }}</dd>
            <dt>Project</dt><dd>{{ $task->project?->name ?? '-' }}</dd>
            <dt>Creator</dt><dd>{{ $task->creator?->name ?? 'System' }}</dd>
            <dt>Start</dt><dd>{{ $task->start_date?->format('M d, Y') ?? '-' }}</dd>
            <dt>Due</dt><dd>{{ $task->due_date?->format('M d, Y') ?? '-' }} @if($task->isOverdue()) @include('partials.status-badge', ['status' => 'overdue']) @endif</dd>
            <dt>Estimated</dt><dd>{{ $task->estimated_hours ? $task->estimated_hours.' hours' : '-' }}</dd>
        </dl>
        <div class="mt-3"><div class="progress" role="progressbar" aria-valuenow="{{ (int) $task->progress }}" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar" style="width: {{ (int) $task->progress }}%"></div></div><small>{{ (int) $task->progress }}% task progress</small></div>
    </section>

    <section class="content-card">
        <div class="content-card-header"><div><h2>Actions</h2><p>Work actions available for this status.</p></div></div>
        <div class="d-flex flex-wrap gap-2 mb-3">
            @if(! $activeTimer && ! in_array($task->status, ['completed', 'cancelled'], true))
                <form method="POST" action="{{ route('employee.tasks.start', $task) }}" data-loading-form>@csrf<button class="btn btn-primary" type="submit" data-loading-text="Starting..."><i class="fa-solid fa-play"></i>Start Work</button></form>
            @elseif($activeTimer && $activeTimer->task_id !== $task->id)
                <div class="alert alert-warning w-100 mb-0">You already have an active timer for another task: <strong>{{ $activeTimer->task?->title ?? 'General work' }}</strong>.</div>
            @endif
            @if($activeTimer && $activeTimer->task_id === $task->id)
                <form method="POST" action="{{ route('employee.tasks.stop', $task) }}" class="w-100" data-loading-form>
                    @csrf
                    <label class="form-label" for="notes">Work note</label>
                    <textarea class="form-control mb-2" id="notes" name="notes" rows="2" placeholder="Short note about this session"></textarea>
                    <button class="btn btn-outline-danger" type="submit" data-loading-text="Stopping..."><i class="fa-solid fa-stop"></i>Stop Work</button>
                </form>
            @endif
        </div>

        @if($availableTransitions)
            <div class="d-flex flex-wrap gap-2 mb-3">
                @foreach($availableTransitions as $status)
                    <form method="POST" action="{{ route('employee.tasks.status', $task) }}" data-loading-form>
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="{{ $status }}">
                        @if($status === 'blocked')
                            <textarea class="form-control mb-2" name="blocked_reason" rows="2" placeholder="Blocked reason" required></textarea>
                        @endif
                        <button class="btn {{ $status === 'submitted' ? 'btn-primary' : 'btn-outline-primary' }}" type="submit"><i class="fa-solid fa-arrow-right"></i>{{ $workflow->label($status) }}</button>
                    </form>
                @endforeach
            </div>
        @else
            @include('partials.empty-state', ['icon' => 'fa-circle-check', 'title' => 'No task actions', 'message' => 'This task is waiting on another workflow step.'])
        @endif

        @if(! in_array($task->status, ['completed', 'cancelled'], true))
            <form method="POST" action="{{ route('employee.tasks.progress', $task) }}">
                @csrf
                @method('PATCH')
                <label class="form-label" for="progress">Update progress</label>
                <div class="input-group"><input class="form-control" id="progress" type="number" min="0" max="100" name="progress" value="{{ old('progress', $task->progress) }}"><button class="btn btn-outline-primary"><i class="fa-solid fa-check"></i>Save</button></div>
            </form>
        @endif
    </section>
</div>

@if($task->description || $task->blocked_reason)
    <section class="content-card mb-3">
        <div class="content-card-header"><div><h2>Work Brief</h2><p>Description and latest feedback.</p></div></div>
        @if($task->description)<p>{{ $task->description }}</p>@endif
        @if($task->blocked_reason)<p class="mb-0"><strong>Latest blocker or review note:</strong> {{ $task->blocked_reason }}</p>@endif
    </section>
@endif

<div class="content-grid mb-3">
    <section class="content-card">
        <div class="content-card-header"><div><h2>Comments</h2><p>Task discussion and review feedback.</p></div></div>
        <form method="POST" action="{{ route('employee.tasks.comments.store', $task) }}" class="mb-3" data-loading-form>
            @csrf
            <label class="form-label" for="comment">Comment</label>
            <textarea class="form-control mb-2" id="comment" name="comment" rows="3" required>{{ old('comment') }}</textarea>
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-comment"></i>Add Comment</button>
        </form>
        <div class="activity-list">@forelse($task->comments->sortByDesc('created_at') as $comment)<div><strong>{{ $comment->user?->name ?? 'System' }}</strong><span>{{ $comment->comment }} &middot; {{ $comment->created_at->diffForHumans() }}</span></div>@empty @include('partials.empty-state', ['icon' => 'fa-comments', 'title' => 'No comments', 'message' => 'Task discussion appears here.']) @endforelse</div>
    </section>
    <section class="content-card">
        <div class="content-card-header"><div><h2>Attachments</h2><p>Files uploaded for this task.</p></div></div>
        <form method="POST" action="{{ route('employee.tasks.files.store', $task) }}" enctype="multipart/form-data" class="mb-3" data-loading-form>
            @csrf
            <label class="form-label" for="file">Upload file</label>
            <div class="input-group"><input class="form-control" id="file" type="file" name="file" required><button class="btn btn-primary" type="submit"><i class="fa-solid fa-upload"></i>Upload</button></div>
        </form>
        <div class="activity-list">@forelse($task->files->sortByDesc('created_at') as $file)<div><strong>{{ $file->original_name }}</strong><span>{{ number_format(($file->size ?? 0) / 1024, 1) }} KB &middot; {{ $file->uploader?->name ?? 'System' }}</span><a class="btn btn-sm btn-outline-primary" href="{{ route('employee.files.download', $file) }}">Download</a></div>@empty @include('partials.empty-state', ['icon' => 'fa-folder-open', 'title' => 'No attachments', 'message' => 'Uploaded work files appear here.']) @endforelse</div>
    </section>
</div>

<section class="content-card">
    <div class="content-card-header"><div><h2>Work Logs</h2><p>Your tracked time for this task.</p></div></div>
    <div class="activity-list">@forelse($task->workSessions->sortByDesc('started_at') as $session)<div><strong>{{ $session->started_at?->format('M d, Y H:i') }}</strong><span>{{ $session->ended_at?->format('H:i') ?? 'Running' }} &middot; {{ $session->duration_minutes }} minutes &middot; {{ $session->notes }}</span></div>@empty @include('partials.empty-state', ['icon' => 'fa-clock', 'title' => 'No work logs', 'message' => 'Tracked time for this task appears here.']) @endforelse</div>
</section>
@endsection
