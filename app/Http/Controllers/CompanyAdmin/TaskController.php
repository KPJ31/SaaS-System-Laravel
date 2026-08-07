<?php

namespace App\Http\Controllers\CompanyAdmin;

use App\Http\Controllers\CompanyAdmin\Concerns\HandlesCompanyAccess;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use App\Models\WorkFile;
use App\Services\AuditLogger;
use App\Services\ProjectProgressService;
use App\Services\TaskNotificationService;
use App\Services\TaskWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TaskController extends Controller
{
    use HandlesCompanyAccess;

    public function index(Request $request): View
    {
        $workflow = app(TaskWorkflowService::class);
        $this->authorizeTaskFilters($request);

        $baseQuery = Task::where('company_id', $this->companyId());

        $tasks = Task::with(['project', 'assignee'])
            ->where('company_id', $this->companyId())
            ->when($request->search, fn ($query, $search) => $query->where(fn ($q) => $q
                ->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhereHas('project', fn ($project) => $project->where('name', 'like', "%{$search}%"))
                ->orWhereHas('assignee', fn ($assignee) => $assignee->where('name', 'like', "%{$search}%"))))
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->when($request->priority, fn ($query, $priority) => $query->where('priority', $priority))
            ->when($request->project_id, fn ($query, $projectId) => $query->where('project_id', $projectId))
            ->when($request->assignee_id, fn ($query, $assigneeId) => $query->where('assignee_id', $assigneeId))
            ->when($request->due === 'today', fn ($query) => $query->whereDate('due_date', today()))
            ->when($request->due === 'overdue', fn ($query) => $query->whereDate('due_date', '<', today())->whereNotIn('status', TaskWorkflowService::TERMINAL_STATUSES))
            ->when($request->due === 'upcoming', fn ($query) => $query->whereDate('due_date', '>=', today())->whereDate('due_date', '<=', today()->addDays(7))->whereNotIn('status', TaskWorkflowService::TERMINAL_STATUSES))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('company-admin.tasks.index', [
            'tasks' => $tasks,
            'projects' => Project::where('company_id', $this->companyId())->orderBy('name')->get(),
            'employees' => User::where('company_id', $this->companyId())->where('role', 'employee')->orderBy('name')->get(),
            'summary' => [
                'todo' => (clone $baseQuery)->whereIn('status', TaskWorkflowService::GROUPS['todo'])->count(),
                'in_progress' => (clone $baseQuery)->whereIn('status', TaskWorkflowService::GROUPS['in_progress'])->count(),
                'review' => (clone $baseQuery)->whereIn('status', TaskWorkflowService::GROUPS['review'])->count(),
                'completed' => (clone $baseQuery)->where('status', 'completed')->count(),
                'overdue' => (clone $baseQuery)->whereDate('due_date', '<', today())->whereNotIn('status', TaskWorkflowService::TERMINAL_STATUSES)->count(),
            ],
            'statuses' => TaskWorkflowService::STATUSES,
            'priorities' => TaskWorkflowService::PRIORITIES,
            'workflow' => $workflow,
        ]);
    }

    public function kanban(Request $request): View
    {
        $this->authorizeTaskFilters($request);

        $query = Task::with(['project:id,name', 'assignee:id,name,avatar'])
            ->where('company_id', $this->companyId())
            ->when($request->project_id, fn ($q, $projectId) => $q->where('project_id', $projectId))
            ->when($request->assignee_id, fn ($q, $assigneeId) => $q->where('assignee_id', $assigneeId))
            ->when($request->priority, fn ($q, $priority) => $q->where('priority', $priority))
            ->when($request->search, fn ($q, $search) => $q->where('title', 'like', "%{$search}%"))
            ->latest();

        $tasks = $query->get();

        return view('company-admin.tasks.kanban', [
            'tasksByGroup' => collect(TaskWorkflowService::GROUPS)
                ->map(fn (array $statuses) => $tasks->filter(fn (Task $task) => in_array($task->status, $statuses, true))->values()),
            'groups' => TaskWorkflowService::GROUPS,
            'projects' => Project::where('company_id', $this->companyId())->orderBy('name')->get(['id', 'name']),
            'employees' => User::where('company_id', $this->companyId())->where('role', 'employee')->orderBy('name')->get(['id', 'name']),
            'workflow' => app(TaskWorkflowService::class),
        ]);
    }

    public function create(): View
    {
        return $this->form(new Task());
    }

    public function store(Request $request, AuditLogger $logger, TaskNotificationService $notifications): RedirectResponse
    {
        $data = $this->validated($request);
        $data['company_id'] = $this->companyId();
        $data['created_by'] = auth()->id();
        $this->validateRelated($data);

        $task = Task::create($data);
        $this->syncProjectProgress($task->project);
        $logger->record('task_created', 'Task created.', auth()->user(), $task, $this->companyId(), request: $request);

        if ($task->assignee_id) {
            $notifications->assigned($task);
        }

        return redirect()->route('company-admin.tasks.show', $task)->with('success', 'Task created successfully.');
    }

    public function show(Task $task): View
    {
        $this->abortUnlessCompanyRecord($task);

        return view('company-admin.tasks.show', [
            'task' => $task->load(['project', 'assignee', 'creator', 'workSessions.user', 'comments.user', 'files.uploader']),
            'availableTransitions' => app(TaskWorkflowService::class)->availableTransitions($task, auth()->user()),
            'activityLogs' => AuditLog::with('user')
                ->where('company_id', $this->companyId())
                ->where(fn ($query) => $query
                    ->where(fn ($q) => $q->where('auditable_type', Task::class)->where('auditable_id', $task->id))
                    ->orWhere(fn ($q) => $q->where('auditable_type', TaskComment::class)->whereIn('auditable_id', $task->comments()->pluck('id')))
                    ->orWhere(fn ($q) => $q->where('auditable_type', WorkFile::class)->whereIn('auditable_id', $task->files()->pluck('id'))))
                ->latest()
                ->take(12)
                ->get(),
            'totalHours' => round($task->workSessions()->sum('duration_minutes') / 60, 2),
            'workflow' => app(TaskWorkflowService::class),
        ]);
    }

    public function edit(Task $task): View
    {
        $this->abortUnlessCompanyRecord($task);

        return $this->form($task);
    }

    public function update(Request $request, Task $task, AuditLogger $logger, TaskNotificationService $notifications): RedirectResponse
    {
        $this->abortUnlessCompanyRecord($task);
        $oldProject = $task->project;
        $oldAssigneeId = $task->assignee_id;
        $data = $this->validated($request);
        $this->validateRelated($data);
        $data['completed_at'] = $data['status'] === 'completed' ? now() : null;
        $task->update($data);
        $this->syncProjectProgress($task->project);
        if ($oldProject && (int) $oldProject->id !== (int) $task->project_id) {
            $this->syncProjectProgress($oldProject);
        }
        $logger->record('task_updated', 'Task updated.', auth()->user(), $task, $this->companyId(), request: $request);

        if ($task->assignee_id && (int) $oldAssigneeId !== (int) $task->assignee_id) {
            $notifications->assigned($task);
        }

        return redirect()->route('company-admin.tasks.show', $task)->with('success', 'Task updated successfully.');
    }

    public function destroy(Task $task, AuditLogger $logger): RedirectResponse
    {
        $this->abortUnlessCompanyRecord($task);
        $task->update(['status' => 'cancelled', 'completed_at' => null]);
        $this->syncProjectProgress($task->project);
        $logger->record('task_cancelled', 'Task cancelled.', auth()->user(), $task, $this->companyId(), request: request());

        return redirect()->route('company-admin.tasks.index')->with('success', 'Task cancelled.');
    }

    public function updateStatus(Task $task, string $status, AuditLogger $logger, TaskNotificationService $notifications, TaskWorkflowService $workflow): RedirectResponse
    {
        $this->abortUnlessCompanyRecord($task);

        if (! $workflow->canTransition($task, $status, auth()->user())) {
            return back()->with('error', 'This task cannot be changed from '.$task->status.' to '.$status.'.');
        }

        $task->update([
            'status' => $status,
            'progress' => $status === 'completed' ? 100 : $task->progress,
            'completed_at' => $status === 'completed' ? now() : null,
        ]);
        $this->syncProjectProgress($task->project);
        $logger->record('task_status_updated', 'Task status updated to '.$status.'.', auth()->user(), $task, $this->companyId(), request: request());
        $this->notifyForStatus($task, $status, $notifications);

        return back()->with('success', 'Task status updated.');
    }

    public function move(Request $request, Task $task, AuditLogger $logger, TaskNotificationService $notifications, TaskWorkflowService $workflow): RedirectResponse
    {
        $this->abortUnlessCompanyRecord($task);
        $data = $request->validate(['status' => ['required', Rule::in(TaskWorkflowService::STATUSES)]]);

        if (! $workflow->canTransition($task, $data['status'], auth()->user())) {
            return back()->with('error', 'This task cannot be moved to '.str_replace('_', ' ', $data['status']).'.');
        }

        $task->update([
            'status' => $data['status'],
            'progress' => $data['status'] === 'completed' ? 100 : $task->progress,
            'completed_at' => $data['status'] === 'completed' ? now() : null,
        ]);
        $this->syncProjectProgress($task->project);
        $logger->record('task_moved', 'Task moved on Kanban board.', auth()->user(), $task, $this->companyId(), ['status' => $data['status']], $request);
        $this->notifyForStatus($task, $data['status'], $notifications);

        return back()->with('success', 'Task moved.');
    }

    public function review(Request $request, Task $task, AuditLogger $logger, TaskNotificationService $notifications, TaskWorkflowService $workflow): RedirectResponse
    {
        $this->abortUnlessCompanyRecord($task);
        $data = $request->validate([
            'status' => ['required', Rule::in(['under_review', 'in_progress', 'completed', 'cancelled'])],
            'blocked_reason' => ['nullable', 'required_if:status,in_progress', 'string', 'max:1000'],
        ]);

        if (! $workflow->canTransition($task, $data['status'], auth()->user())) {
            return back()->with('error', 'This task cannot be changed from '.$task->status.' to '.$data['status'].'.');
        }

        $task->update([
            'status' => $data['status'],
            'progress' => $data['status'] === 'completed' ? 100 : $task->progress,
            'completed_at' => $data['status'] === 'completed' ? now() : null,
            'blocked_reason' => $data['blocked_reason'] ?? null,
        ]);
        if (($data['status'] === 'in_progress') && ! empty($data['blocked_reason'])) {
            TaskComment::create([
                'company_id' => $this->companyId(),
                'task_id' => $task->id,
                'user_id' => auth()->id(),
                'comment' => 'Review feedback: '.$data['blocked_reason'],
            ]);
        }
        $this->syncProjectProgress($task->project);
        $logger->record('task_'.$data['status'], 'Task reviewed by Company Admin.', auth()->user(), $task, $this->companyId(), request: $request);
        $this->notifyForStatus($task, $data['status'], $notifications);

        return back()->with('success', 'Task review status updated.');
    }

    public function comment(Request $request, Task $task, AuditLogger $logger): RedirectResponse
    {
        $this->abortUnlessCompanyRecord($task);
        $data = $request->validate(['comment' => ['required', 'string', 'max:2000']]);
        $comment = TaskComment::create($data + ['company_id' => $this->companyId(), 'task_id' => $task->id, 'user_id' => auth()->id()]);
        $logger->record('comment_added', 'Company Admin added a task comment.', auth()->user(), $comment, $this->companyId(), request: $request);

        return back()->with('success', 'Comment added.');
    }

    public function upload(Request $request, Task $task, AuditLogger $logger): RedirectResponse
    {
        $this->abortUnlessCompanyRecord($task);
        $data = $request->validate(['file' => ['required', 'file', 'mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg,webp,zip', 'max:5120']]);
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
            'visibility' => 'company',
        ]);
        $logger->record('file_uploaded', 'Company Admin uploaded a task file.', auth()->user(), $file, $this->companyId(), request: $request);

        return back()->with('success', 'File uploaded.');
    }

    public function download(WorkFile $file)
    {
        abort_unless($file->company_id === $this->companyId(), 403);
        abort_unless(Storage::disk('public')->exists($file->path), 404);

        return Storage::disk('public')->download($file->path, $file->original_name);
    }

    private function form(Task $task): View
    {
        return view('company-admin.tasks.form', [
            'task' => $task,
            'projects' => Project::where('company_id', $this->companyId())->orderBy('name')->get(),
            'employees' => User::where('company_id', $this->companyId())->where('role', 'employee')->where('status', 'active')->orderBy('name')->get(),
            'statuses' => TaskWorkflowService::STATUSES,
            'priorities' => TaskWorkflowService::PRIORITIES,
            'types' => TaskWorkflowService::TYPES,
        ]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'project_id' => ['required', 'integer', Rule::exists('projects', 'id')->where(fn ($query) => $query->where('company_id', $this->companyId()))],
            'assignee_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where(fn ($query) => $query->where('company_id', $this->companyId())->where('role', 'employee')->where('status', 'active'))],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:4000'],
            'priority' => ['required', Rule::in(TaskWorkflowService::PRIORITIES)],
            'status' => ['required', Rule::in(TaskWorkflowService::STATUSES)],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0'],
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
            'task_type' => ['required', Rule::in(TaskWorkflowService::TYPES)],
        ]);
    }

    private function validateRelated(array $data): void
    {
        abort_unless(Project::where('company_id', $this->companyId())->whereKey($data['project_id'])->exists(), 403);

        if (! empty($data['assignee_id'])) {
            abort_unless(User::where('company_id', $this->companyId())->where('role', 'employee')->whereKey($data['assignee_id'])->exists(), 403);
        }
    }

    private function authorizeTaskFilters(Request $request): void
    {
        abort_unless(! $request->filled('status') || in_array($request->status, TaskWorkflowService::STATUSES, true), 404);
        abort_unless(! $request->filled('priority') || in_array($request->priority, TaskWorkflowService::PRIORITIES, true), 404);
        abort_unless(! $request->filled('due') || in_array($request->due, ['today', 'overdue', 'upcoming'], true), 404);

        if ($request->filled('project_id')) {
            abort_unless(Project::where('company_id', $this->companyId())->whereKey($request->integer('project_id'))->exists(), 403);
        }

        if ($request->filled('assignee_id')) {
            abort_unless(User::where('company_id', $this->companyId())->where('role', 'employee')->whereKey($request->integer('assignee_id'))->exists(), 403);
        }
    }

    private function notifyForStatus(Task $task, string $status, TaskNotificationService $notifications): void
    {
        match ($status) {
            'completed' => $notifications->completed($task),
            'in_progress' => $notifications->sentBack($task),
            default => null,
        };
    }

    private function syncProjectProgress(Project $project): void
    {
        app(ProjectProgressService::class)->sync($project);
    }
}
