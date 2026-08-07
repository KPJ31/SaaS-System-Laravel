@extends('layouts.app')

@section('title', $task->title.' - Elevanix')

@section('content')
@include('partials.page-header', [
    'eyebrow' => 'Task Workspace',
    'title' => $task->title,
    'description' => ($task->project?->name ?? 'No project').' - '.str_replace('_', ' ', ucfirst($task->status)),
    'actions' => new \Illuminate\Support\HtmlString('<a class="btn btn-outline-primary" href="'.route('company-admin.tasks.kanban').'"><i class="fa-solid fa-table-columns"></i>Kanban</a><a class="btn btn-primary" href="'.route('company-admin.tasks.edit', $task).'"><i class="fa-solid fa-pen"></i>Edit</a>'),
])

<div class="stat-grid mb-3">
    @include('partials.stat-card', ['label' => 'Progress', 'value' => (int) $task->progress.'%', 'icon' => 'fa-bars-progress', 'tone' => 'green'])
    @include('partials.stat-card', ['label' => 'Logged Hours', 'value' => number_format($totalHours, 1), 'icon' => 'fa-clock', 'tone' => 'blue'])
    @include('partials.stat-card', ['label' => 'Comments', 'value' => $task->comments->count(), 'icon' => 'fa-comments'])
    @include('partials.stat-card', ['label' => 'Files', 'value' => $task->files->count(), 'icon' => 'fa-folder-open'])
</div>

<div class="content-grid mb-3">
    <section class="content-card">
        <div class="content-card-header"><div><h2>Overview</h2><p>Assignment, dates and workflow state.</p></div></div>
        <dl class="detail-list mt-3">
            <dt>Status</dt><dd>@include('partials.status-badge', ['status' => $task->status])</dd>
            <dt>Priority</dt><dd>@include('partials.priority-badge', ['priority' => $task->priority])</dd>
            <dt>Project</dt><dd>{{ $task->project?->name ?? '-' }}</dd>
            <dt>Assignee</dt><dd>{{ $task->assignee?->name ?? 'Unassigned' }}</dd>
            <dt>Creator</dt><dd>{{ $task->creator?->name ?? 'System' }}</dd>
            <dt>Type</dt><dd>{{ ucfirst($task->task_type ?? 'task') }}</dd>
            <dt>Start</dt><dd>{{ $task->start_date?->format('M d, Y') ?? '-' }}</dd>
            <dt>Due</dt><dd>{{ $task->due_date?->format('M d, Y') ?? '-' }} @if($task->isOverdue()) @include('partials.status-badge', ['status' => 'overdue']) @endif</dd>
            <dt>Estimated</dt><dd>{{ $task->estimated_hours ? $task->estimated_hours.' hours' : '-' }}</dd>
            <dt>Completed</dt><dd>{{ $task->completed_at?->format('M d, Y H:i') ?? '-' }}</dd>
        </dl>
        <div class="mt-3"><div class="progress" role="progressbar" aria-valuenow="{{ (int) $task->progress }}" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar" style="width: {{ (int) $task->progress }}%"></div></div><small>{{ (int) $task->progress }}% task progress</small></div>
    </section>

    <section class="content-card">
        <div class="content-card-header"><div><h2>Status Actions</h2><p>Only valid workflow moves are shown.</p></div></div>
        @if($availableTransitions)
            <div class="d-flex flex-wrap gap-2 mb-3">
                @foreach($availableTransitions as $status)
                    @if(in_array($status, ['under_review', 'completed', 'in_progress', 'cancelled'], true))
                        <form method="POST" action="{{ route('company-admin.tasks.review', $task) }}" data-loading-form @if($status === 'cancelled') data-confirm="Cancel this task?" @endif>
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="{{ $status }}">
                            @if($status === 'in_progress')
                                <textarea class="form-control mb-2" name="blocked_reason" rows="2" placeholder="Feedback for assignee" required>{{ old('blocked_reason') }}</textarea>
                            @endif
                            <button class="btn {{ $status === 'completed' ? 'btn-primary' : 'btn-outline-primary' }}" type="submit">
                                <i class="fa-solid {{ $status === 'completed' ? 'fa-check' : ($status === 'cancelled' ? 'fa-ban' : 'fa-arrow-right') }}"></i>{{ $workflow->label($status) }}
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('company-admin.tasks.status', [$task, $status]) }}" data-loading-form>
                            @csrf
                            <button class="btn btn-outline-primary" type="submit"><i class="fa-solid fa-arrow-right"></i>{{ $workflow->label($status) }}</button>
                        </form>
                    @endif
                @endforeach
            </div>
        @else
            @include('partials.empty-state', ['icon' => 'fa-circle-check', 'title' => 'No status actions', 'message' => 'This task has no valid next workflow action.'])
        @endif
        @if($task->blocked_reason)<p class="mb-0"><strong>Latest blocker or review note:</strong> {{ $task->blocked_reason }}</p>@endif
    </section>
</div>

@if($task->description)
    <section class="content-card mb-3"><div class="content-card-header"><div><h2>Description</h2><p>Work details and expected outcome.</p></div></div><p class="mb-0">{{ $task->description }}</p></section>
@endif

<div class="content-grid mb-3">
    <section class="content-card">
        <div class="content-card-header"><div><h2>Comments</h2><p>Review feedback and task discussion.</p></div></div>
        <form method="POST" action="{{ route('company-admin.tasks.comments.store', $task) }}" class="mb-3" data-loading-form>
            @csrf
            <label class="form-label" for="comment">Comment</label>
            <textarea class="form-control mb-2" id="comment" name="comment" rows="3" required>{{ old('comment') }}</textarea>
            <button class="btn btn-outline-primary" type="submit"><i class="fa-solid fa-comment"></i>Add Comment</button>
        </form>
        <div class="activity-list">
            @forelse($task->comments->sortByDesc('created_at') as $comment)
                <div><strong>{{ $comment->user?->name ?? 'System' }}</strong><span>{{ $comment->comment }} &middot; {{ $comment->created_at->diffForHumans() }}</span></div>
            @empty
                @include('partials.empty-state', ['icon' => 'fa-comments', 'title' => 'No comments', 'message' => 'Task discussion appears here.'])
            @endforelse
        </div>
    </section>

    <section class="content-card">
        <div class="content-card-header"><div><h2>Attachments</h2><p>Files connected to this task.</p></div></div>
        <form method="POST" action="{{ route('company-admin.tasks.files.store', $task) }}" enctype="multipart/form-data" class="mb-3" data-loading-form>
            @csrf
            <label class="form-label" for="file">Upload file</label>
            <div class="input-group"><input class="form-control" id="file" type="file" name="file" required><button class="btn btn-outline-primary" type="submit"><i class="fa-solid fa-upload"></i>Upload</button></div>
        </form>
        <div class="activity-list">
            @forelse($task->files->sortByDesc('created_at') as $file)
                <div><strong>{{ $file->original_name }}</strong><span>{{ number_format(($file->size ?? 0) / 1024, 1) }} KB &middot; {{ $file->uploader?->name ?? 'System' }}</span><a class="btn btn-sm btn-outline-primary" href="{{ route('company-admin.files.download', $file) }}">Download</a></div>
            @empty
                @include('partials.empty-state', ['icon' => 'fa-folder-open', 'title' => 'No attachments', 'message' => 'Task files appear here.'])
            @endforelse
        </div>
    </section>
</div>

<div class="content-grid">
    <section class="content-card">
        <div class="content-card-header"><div><h2>Work Logs</h2><p>Time tracked against this task.</p></div></div>
        <div class="activity-list">
            @forelse($task->workSessions->sortByDesc('started_at') as $session)
                <div><strong>{{ $session->user?->name ?? 'Unknown user' }}</strong><span>{{ $session->started_at?->format('M d, Y H:i') }} - {{ $session->ended_at?->format('H:i') ?? 'Running' }} &middot; {{ number_format(($session->duration_minutes ?? 0) / 60, 1) }} hours</span></div>
            @empty
                @include('partials.empty-state', ['icon' => 'fa-clock', 'title' => 'No work logs', 'message' => 'Employee tracked time appears here.'])
            @endforelse
        </div>
    </section>

    <section class="content-card">
        <div class="content-card-header"><div><h2>Activity</h2><p>Recent audited task events.</p></div></div>
        <div class="activity-list">
            @forelse($activityLogs as $log)
                <div><strong>{{ str_replace('_', ' ', ucfirst($log->action)) }}</strong><span>{{ $log->description }} &middot; {{ $log->user?->name ?? 'System' }} &middot; {{ $log->created_at->format('M d, Y H:i') }}</span></div>
            @empty
                @include('partials.empty-state', ['icon' => 'fa-clipboard-list', 'title' => 'No activity', 'message' => 'Task activity appears here.'])
            @endforelse
        </div>
    </section>
</div>
@endsection
