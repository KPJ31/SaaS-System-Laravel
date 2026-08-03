<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Employee\Concerns\HandlesEmployeeAccess;
use App\Models\Project;
use App\Models\Task;
use App\Models\WorkSession;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WorkSessionController extends Controller
{
    use HandlesEmployeeAccess;

    public function index(Request $request): View
    {
        $sessions = $this->filtered($request)
            ->latest('started_at')
            ->paginate(10)
            ->withQueryString();

        $base = WorkSession::where('company_id', $this->companyId())->where('user_id', auth()->id());

        return view('employee.work-sessions.index', [
            'sessions' => $sessions,
            'projects' => Project::where('company_id', $this->companyId())->whereHas('tasks', fn ($query) => $query->where('assignee_id', auth()->id()))->orderBy('name')->get(),
            'tasks' => Task::where('company_id', $this->companyId())->where('assignee_id', auth()->id())->orderBy('title')->get(),
            'dailyTotal' => (clone $base)->whereDate('started_at', today())->sum('duration_minutes'),
            'weeklyTotal' => (clone $base)->whereBetween('started_at', [now()->startOfWeek(), now()->endOfWeek()])->sum('duration_minutes'),
            'monthlyTotal' => (clone $base)->whereMonth('started_at', now()->month)->whereYear('started_at', now()->year)->sum('duration_minutes'),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $sessions = $this->filtered($request)->latest('started_at')->get();

        return response()->streamDownload(function () use ($request, $sessions): void {
            $out = fopen('php://output', 'w');
            $this->writeCsv($out, 'My Work Sessions Report', $this->headers(), $this->rows($sessions), $request);
            fclose($out);
        }, 'my-work-sessions.csv');
    }

    public function exportPdf(Request $request)
    {
        $sessions = $this->filtered($request)->latest('started_at')->get();

        return Pdf::loadView('reports.table-pdf', [
            'title' => 'My Work Sessions Report',
            'scope' => auth()->user()->name,
            'generatedAt' => now(),
            'headers' => $this->headers(),
            'rows' => $this->rows($sessions),
            'filters' => $this->activeFilters($request),
        ])->setPaper('a4', 'landscape')->download('my-work-sessions.pdf');
    }

    private function filtered(Request $request)
    {
        $this->authorizeFilters($request);

        return WorkSession::with(['project', 'task'])
            ->where('company_id', $this->companyId())
            ->where('user_id', auth()->id())
            ->when($request->search, fn ($query, $search) => $query->where('notes', 'like', "%{$search}%"))
            ->when($request->project_id, fn ($query, $id) => $query->where('project_id', $id))
            ->when($request->task_id, fn ($query, $id) => $query->where('task_id', $id))
            ->when($request->date, fn ($query, $date) => $query->whereDate('started_at', $date));
    }

    private function headers(): array
    {
        return ['Date', 'Project', 'Task', 'Start', 'Stop', 'Duration Minutes', 'Note', 'Status'];
    }

    private function rows($sessions)
    {
        return $sessions->map(fn (WorkSession $session) => [
            $session->started_at?->toDateString() ?? '-',
            $session->project?->name ?? '-',
            $session->task?->title ?? '-',
            $session->started_at?->format('H:i') ?? '-',
            $session->ended_at?->format('H:i') ?? 'Running',
            $session->duration_minutes,
            $session->notes ?? '-',
            $session->status,
        ]);
    }

    private function writeCsv($out, string $title, array $headers, $rows, Request $request): void
    {
        fputcsv($out, $this->safeCsvRow([$title]));
        fputcsv($out, $this->safeCsvRow(['Employee', auth()->user()->name]));
        fputcsv($out, $this->safeCsvRow(['Generated At', now()->format('Y-m-d H:i')]));
        foreach ($this->activeFilters($request) as $label => $value) {
            fputcsv($out, $this->safeCsvRow([$label, $value]));
        }
        fputcsv($out, []);
        fputcsv($out, $this->safeCsvRow($headers));
        foreach ($rows as $row) {
            fputcsv($out, $this->safeCsvRow($row));
        }
    }

    private function activeFilters(Request $request): array
    {
        return collect($request->only(['search', 'project_id', 'task_id', 'date']))
            ->filter(fn ($value) => filled($value))
            ->mapWithKeys(fn ($value, $key) => [str_replace('_', ' ', ucfirst($key)) => (string) $value])
            ->all();
    }

    private function authorizeFilters(Request $request): void
    {
        if ($request->filled('project_id')) {
            abort_unless(Project::where('company_id', $this->companyId())
                ->whereKey($request->integer('project_id'))
                ->whereHas('tasks', fn ($query) => $query->where('assignee_id', auth()->id()))
                ->exists(), 403);
        }

        if ($request->filled('task_id')) {
            abort_unless(Task::where('company_id', $this->companyId())->where('assignee_id', auth()->id())->whereKey($request->integer('task_id'))->exists(), 403);
        }
    }

    private function safeCsvRow(iterable $row): array
    {
        return collect($row)
            ->map(function ($value): string {
                $value = (string) $value;

                return preg_match('/^[=+\-@]/', $value) ? "'".$value : $value;
            })
            ->all();
    }
}
