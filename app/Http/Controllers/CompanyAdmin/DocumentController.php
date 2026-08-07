<?php

namespace App\Http\Controllers\CompanyAdmin;

use App\Http\Controllers\CompanyAdmin\Concerns\HandlesCompanyAccess;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Models\WorkFile;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DocumentController extends Controller
{
    use HandlesCompanyAccess;

    public function index(Request $request): View
    {
        $this->authorizeFilters($request);

        $files = WorkFile::with(['project', 'task', 'uploader'])
            ->where('company_id', $this->companyId())
            ->when($request->project_id, fn ($query, $id) => $query->where('project_id', $id))
            ->when($request->task_id, fn ($query, $id) => $query->where('task_id', $id))
            ->when($request->type, fn ($query, $type) => $query->where('original_name', 'like', '%.'.$type))
            ->when($request->date, fn ($query, $date) => $query->whereDate('created_at', $date))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('company-admin.documents.index', [
            'files' => $files,
            'projects' => Project::where('company_id', $this->companyId())->orderBy('name')->get(),
            'tasks' => Task::where('company_id', $this->companyId())->orderBy('title')->get(),
        ]);
    }

    public function store(Request $request, AuditLogger $logger): RedirectResponse
    {
        $data = $request->validate([
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'task_id' => ['nullable', 'integer', 'exists:tasks,id'],
            'file' => ['required', 'file', 'mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg,webp,zip', 'max:5120'],
        ]);
        $this->validateRelated($data);
        $uploaded = $data['file'];
        $path = $uploaded->store('work-files/'.$this->companyId(), 'public');
        $file = WorkFile::create([
            'company_id' => $this->companyId(),
            'project_id' => $data['project_id'] ?? null,
            'task_id' => $data['task_id'] ?? null,
            'uploaded_by' => auth()->id(),
            'original_name' => $uploaded->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $uploaded->getMimeType(),
            'size' => $uploaded->getSize(),
            'visibility' => 'company',
        ]);
        $logger->record('file_uploaded', 'Company document uploaded.', auth()->user(), $file, $this->companyId(), request: $request);

        return back()->with('success', 'Document uploaded.');
    }

    public function download(WorkFile $file)
    {
        abort_unless($file->company_id === $this->companyId(), 403);
        abort_unless(Storage::disk('public')->exists($file->path), 404);

        return Storage::disk('public')->download($file->path, $file->original_name);
    }

    public function destroy(WorkFile $file): RedirectResponse
    {
        abort_unless($file->company_id === $this->companyId(), 403);
        Storage::disk('public')->delete($file->path);
        $file->delete();

        return back()->with('success', 'Document deleted.');
    }

    private function validateRelated(array $data): void
    {
        if (! empty($data['project_id'])) {
            abort_unless(Project::where('company_id', $this->companyId())->whereKey($data['project_id'])->exists(), 403);
        }

        if (! empty($data['task_id'])) {
            $task = Task::where('company_id', $this->companyId())->whereKey($data['task_id'])->firstOrFail();
            abort_unless(empty($data['project_id']) || (int) $task->project_id === (int) $data['project_id'], 403);
        }
    }

    private function authorizeFilters(Request $request): void
    {
        if ($request->filled('project_id')) {
            abort_unless(Project::where('company_id', $this->companyId())->whereKey($request->integer('project_id'))->exists(), 403);
        }

        if ($request->filled('task_id')) {
            abort_unless(Task::where('company_id', $this->companyId())->whereKey($request->integer('task_id'))->exists(), 403);
        }
    }
}
