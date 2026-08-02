<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Employee\Concerns\HandlesEmployeeAccess;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\WorkFile;
use App\Services\AuditLogger;
use App\Services\WorkTimerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TaskController extends Controller
{
    use HandlesEmployeeAccess;

    public function index(Request $request): View
    {
        $tasks = Task::with('project')
            ->where('company_id', $this->companyId())
            ->where('assignee_id', auth()->id())
            ->when($request->search, fn ($query, $search) => $query->where('title', 'like', "%{$search}%"))
            ->when($request->project_id, fn ($query, $projectId) => $query->where('project_id', $projectId))
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->when($request->priority, fn ($query, $priority) => $query->where('priority', $priority))
            ->when($request->due === 'overdue', fn ($query) => $query->whereDate('due_date', '<', today())->whereNotIn('status', ['completed', 'cancelled']))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('employee.tasks.index', [
            'tasks' => $tasks,
            'projects' => Project::where('company_id', $this->companyId())->whereHas('tasks', fn ($query) => $query->where('assignee_id', auth()->id()))->orderBy('name')->get(),
        ]);
    }

    public function show(Task $task, AuditLogger $logger): View
    {
        $this->abortUnlessOwnTask($task);
        $logger->record('viewed', 'Task viewed by employee.', auth()->user(), $task, $this->companyId(), request: request());

        return view('employee.tasks.show', [
            'task' => $task->load(['project', 'assignee', 'comments.user', 'files.uploader', 'workSessions' => fn ($query) => $query->where('user_id', auth()->id())->latest()]),
            'activeTimer' => $this->activeTimer(),
        ]);
    }

    public function start(Task $task, WorkTimerService $timer, AuditLogger $logger): RedirectResponse
    {
        $this->abortUnlessOwnTask($task);
        $session = $timer->start(auth()->user(), $task->project, $task);
        $logger->record('timer_started', 'Work timer started.', auth()->user(), $session, $this->companyId(), request: request());

        return back()->with('success', 'Work timer started.');
    }

    public function stop(Task $task, Request $request, WorkTimerService $timer, AuditLogger $logger): RedirectResponse
    {
        $this->abortUnlessOwnTask($task);
        $data = $request->validate(['notes' => ['nullable', 'string', 'max:1000']]);
        $session = $task->workSessions()->where('user_id', auth()->id())->whereNull('ended_at')->firstOrFail();
        $timer->stop(auth()->user(), $session, $data['notes'] ?? null);
        $logger->record('timer_stopped', 'Work timer stopped.', auth()->user(), $session, $this->companyId(), request: $request);

        return back()->with('success', 'Work timer stopped.');
    }

    public function progress(Task $task, Request $request, AuditLogger $logger): RedirectResponse
    {
        $this->abortUnlessOwnTask($task);
        abort_if(in_array($task->status, ['completed', 'cancelled'], true), 403);
        $data = $request->validate(['progress' => ['required', 'integer', 'min:0', 'max:100']]);
        $task->update(['progress' => $data['progress']]);
        $logger->record('progress_updated', 'Task progress updated.', auth()->user(), $task, $this->companyId(), ['progress' => $data['progress']], $request);

        return back()->with('success', 'Task progress updated.');
    }

    public function status(Task $task, Request $request, AuditLogger $logger): RedirectResponse
    {
        $this->abortUnlessOwnTask($task);
        $data = $request->validate([
            'status' => ['required', 'in:in_progress,paused,blocked,submitted'],
            'blocked_reason' => ['nullable', 'required_if:status,blocked', 'string', 'max:1000'],
        ]);
        abort_if(in_array($task->status, ['completed', 'cancelled'], true), 403);
        $allowed = [
            'assigned' => ['in_progress'],
            'todo' => ['in_progress'],
            'in_progress' => ['paused', 'blocked', 'submitted'],
            'paused' => ['in_progress'],
            'blocked' => ['in_progress'],
            'under_review' => ['in_progress'],
        ];
        abort_unless(in_array($data['status'], $allowed[$task->status] ?? [], true), 422);
        $task->update([
            'status' => $data['status'],
            'progress' => $data['status'] === 'submitted' ? 100 : $task->progress,
            'blocked_reason' => $data['blocked_reason'] ?? null,
        ]);
        $logger->record('status_updated', 'Task status updated to '.$data['status'].'.', auth()->user(), $task, $this->companyId(), request: $request);

        return back()->with('success', 'Task status updated.');
    }

    public function comment(Task $task, Request $request, AuditLogger $logger): RedirectResponse
    {
        $this->abortUnlessOwnTask($task);
        $data = $request->validate(['comment' => ['required', 'string', 'max:2000']]);
        $comment = TaskComment::create($data + ['company_id' => $this->companyId(), 'task_id' => $task->id, 'user_id' => auth()->id()]);
        $logger->record('comment_added', 'Task comment added.', auth()->user(), $comment, $this->companyId(), request: $request);

        return back()->with('success', 'Comment added.');
    }

    public function upload(Task $task, Request $request, AuditLogger $logger): RedirectResponse
    {
        $this->abortUnlessOwnTask($task);
        $data = $request->validate(['file' => ['required', 'file', 'mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg,zip', 'max:5120']]);
        $uploaded = $data['file'];
        $path = $uploaded->store('work-files/'.$this->companyId(), 'public');
        $file = WorkFile::create([
            'company_id' => $this->companyId(),
            'project_id' => $task->project_id,
            'task_id' => $task->id,
            'uploaded_by' => auth()->id(),
            'original_name' => $uploaded->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $uploaded->getMimeType(),
            'size' => $uploaded->getSize(),
        ]);
        $logger->record('file_uploaded', 'Task file uploaded.', auth()->user(), $file, $this->companyId(), request: $request);

        return back()->with('success', 'File uploaded.');
    }

    public function download(WorkFile $file)
    {
        abort_unless($file->company_id === $this->companyId() && ($file->uploaded_by === auth()->id() || ($file->task_id && Task::whereKey($file->task_id)->where('assignee_id', auth()->id())->exists())), 403);

        return Storage::disk('public')->download($file->path, $file->original_name);
    }
}
