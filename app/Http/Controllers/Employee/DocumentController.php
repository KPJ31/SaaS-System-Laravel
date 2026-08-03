<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Employee\Concerns\HandlesEmployeeAccess;
use App\Models\Project;
use App\Models\Task;
use App\Models\WorkFile;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DocumentController extends Controller
{
    use HandlesEmployeeAccess;

    public function index(Request $request): View
    {
        $taskIds = Task::where('company_id', $this->companyId())->where('assignee_id', auth()->id())->pluck('id');
        $this->authorizeFilters($request, $taskIds);

        $files = WorkFile::with(['project', 'task', 'uploader'])
            ->where('company_id', $this->companyId())
            ->where(fn ($query) => $query->where('uploaded_by', auth()->id())->orWhereIn('task_id', $taskIds))
            ->when($request->project_id, fn ($query, $id) => $query->where('project_id', $id))
            ->when($request->task_id, fn ($query, $id) => $query->where('task_id', $id))
            ->when($request->type, fn ($query, $type) => $query->where('original_name', 'like', '%.'.$type))
            ->when($request->date, fn ($query, $date) => $query->whereDate('created_at', $date))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('employee.documents.index', [
            'files' => $files,
            'projects' => Project::where('company_id', $this->companyId())->whereHas('tasks', fn ($query) => $query->where('assignee_id', auth()->id()))->orderBy('name')->get(),
            'tasks' => Task::where('company_id', $this->companyId())->where('assignee_id', auth()->id())->orderBy('title')->get(),
        ]);
    }

    private function authorizeFilters(Request $request, $taskIds): void
    {
        if ($request->filled('project_id')) {
            abort_unless(Project::where('company_id', $this->companyId())
                ->whereKey($request->integer('project_id'))
                ->whereHas('tasks', fn ($query) => $query->where('assignee_id', auth()->id()))
                ->exists(), 403);
        }

        if ($request->filled('task_id')) {
            abort_unless($taskIds->contains((int) $request->integer('task_id')), 403);
        }
    }
}
