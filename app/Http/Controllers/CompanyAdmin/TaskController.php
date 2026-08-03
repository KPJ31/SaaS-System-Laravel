<?php

namespace App\Http\Controllers\CompanyAdmin;

use App\Http\Controllers\CompanyAdmin\Concerns\HandlesCompanyAccess;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use App\Models\WorkFile;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TaskController extends Controller
{
    use HandlesCompanyAccess;

    public function index(Request $request): View
    {
        $this->authorizeTaskFilters($request);

        $tasks = Task::with(['project', 'assignee'])
            ->where('company_id', $this->companyId())
            ->when($request->search, fn ($query, $search) => $query->where('title', 'like', "%{$search}%"))
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->when($request->priority, fn ($query, $priority) => $query->where('priority', $priority))
            ->when($request->project_id, fn ($query, $projectId) => $query->where('project_id', $projectId))
            ->when($request->assignee_id, fn ($query, $assigneeId) => $query->where('assignee_id', $assigneeId))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('company-admin.tasks.index', [
            'tasks' => $tasks,
            'projects' => Project::where('company_id', $this->companyId())->orderBy('name')->get(),
            'employees' => User::where('company_id', $this->companyId())->where('role', 'employee')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return $this->form(new Task());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['company_id'] = $this->companyId();
        $data['created_by'] = auth()->id();
        $this->validateRelated($data);

        $task = Task::create($data);
        $this->syncProjectProgress($task->project);

        return redirect()->route('company-admin.tasks.show', $task)->with('success', 'Task created successfully.');
    }

    public function show(Task $task): View
    {
        $this->abortUnlessCompanyRecord($task);

        return view('company-admin.tasks.show', ['task' => $task->load(['project', 'assignee', 'workSessions.user', 'comments.user', 'files.uploader'])]);
    }

    public function edit(Task $task): View
    {
        $this->abortUnlessCompanyRecord($task);

        return $this->form($task);
    }

    public function update(Request $request, Task $task): RedirectResponse
    {
        $this->abortUnlessCompanyRecord($task);
        $data = $this->validated($request);
        $this->validateRelated($data);
        $data['completed_at'] = $data['status'] === 'completed' ? now() : null;
        $task->update($data);
        $this->syncProjectProgress($task->project);

        return redirect()->route('company-admin.tasks.show', $task)->with('success', 'Task updated successfully.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $this->abortUnlessCompanyRecord($task);
        abort_if($task->workSessions()->exists(), 422, 'Tasks with work sessions cannot be deleted.');
        $project = $task->project;
        $task->delete();
        $this->syncProjectProgress($project);

        return redirect()->route('company-admin.tasks.index')->with('success', 'Task deleted.');
    }

    public function updateStatus(Task $task, string $status): RedirectResponse
    {
        $this->abortUnlessCompanyRecord($task);
        abort_unless(in_array($status, ['todo', 'assigned', 'in_progress', 'paused', 'blocked', 'submitted', 'under_review', 'completed', 'cancelled'], true), 404);

        if (! $this->canTransition($task->status, $status)) {
            return back()->with('error', 'This task cannot be changed from '.$task->status.' to '.$status.'.');
        }

        $task->update(['status' => $status, 'completed_at' => $status === 'completed' ? now() : null]);
        $this->syncProjectProgress($task->project);

        return back()->with('success', 'Task status updated.');
    }

    public function review(Request $request, Task $task, AuditLogger $logger): RedirectResponse
    {
        $this->abortUnlessCompanyRecord($task);
        $data = $request->validate([
            'status' => ['required', 'in:under_review,in_progress,completed,cancelled'],
            'blocked_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! $this->canTransition($task->status, $data['status'])) {
            return back()->with('error', 'This task cannot be changed from '.$task->status.' to '.$data['status'].'.');
        }

        $task->update([
            'status' => $data['status'],
            'progress' => $data['status'] === 'completed' ? 100 : $task->progress,
            'completed_at' => $data['status'] === 'completed' ? now() : null,
            'blocked_reason' => $data['blocked_reason'] ?? null,
        ]);
        $this->syncProjectProgress($task->project);
        $logger->record('task_'.$data['status'], 'Task reviewed by Company Admin.', auth()->user(), $task, $this->companyId(), request: $request);

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

        return Storage::disk('public')->download($file->path, $file->original_name);
    }

    private function form(Task $task): View
    {
        return view('company-admin.tasks.form', [
            'task' => $task,
            'projects' => Project::where('company_id', $this->companyId())->orderBy('name')->get(),
            'employees' => User::where('company_id', $this->companyId())->where('role', 'employee')->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'assignee_id' => ['nullable', 'integer', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:4000'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'status' => ['required', 'in:todo,assigned,in_progress,paused,blocked,submitted,under_review,completed,cancelled'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0'],
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
            'task_type' => ['required', 'in:task,bug,issue,improvement'],
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
        if ($request->filled('project_id')) {
            abort_unless(Project::where('company_id', $this->companyId())->whereKey($request->integer('project_id'))->exists(), 403);
        }

        if ($request->filled('assignee_id')) {
            abort_unless(User::where('company_id', $this->companyId())->where('role', 'employee')->whereKey($request->integer('assignee_id'))->exists(), 403);
        }
    }

    private function canTransition(string $currentStatus, string $newStatus): bool
    {
        if ($currentStatus === $newStatus) {
            return false;
        }

        return match ($currentStatus) {
            'todo' => in_array($newStatus, ['assigned', 'in_progress', 'cancelled'], true),
            'assigned' => in_array($newStatus, ['in_progress', 'cancelled'], true),
            'in_progress' => in_array($newStatus, ['paused', 'blocked', 'submitted', 'cancelled'], true),
            'paused', 'blocked' => in_array($newStatus, ['in_progress', 'cancelled'], true),
            'submitted' => $newStatus === 'under_review',
            'under_review' => in_array($newStatus, ['completed', 'in_progress', 'cancelled'], true),
            default => false,
        };
    }

    private function syncProjectProgress(Project $project): void
    {
        $total = $project->tasks()->count();

        if ($total > 0) {
            $project->update(['progress' => (int) round(($project->tasks()->where('status', 'completed')->count() / $total) * 100)]);
        }
    }
}
