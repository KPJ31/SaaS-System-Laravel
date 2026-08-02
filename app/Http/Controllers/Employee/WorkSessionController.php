<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Employee\Concerns\HandlesEmployeeAccess;
use App\Models\Project;
use App\Models\Task;
use App\Models\WorkSession;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class WorkSessionController extends Controller
{
    use HandlesEmployeeAccess;

    public function index(Request $request): View
    {
        $sessions = WorkSession::with(['project', 'task'])
            ->where('company_id', $this->companyId())
            ->where('user_id', auth()->id())
            ->when($request->search, fn ($query, $search) => $query->where('notes', 'like', "%{$search}%"))
            ->when($request->project_id, fn ($query, $id) => $query->where('project_id', $id))
            ->when($request->task_id, fn ($query, $id) => $query->where('task_id', $id))
            ->when($request->date, fn ($query, $date) => $query->whereDate('started_at', $date))
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

    public function export(): StreamedResponse
    {
        $sessions = WorkSession::with(['project', 'task'])->where('company_id', $this->companyId())->where('user_id', auth()->id())->latest('started_at')->get();

        return response()->streamDownload(function () use ($sessions): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Project', 'Task', 'Start', 'Stop', 'Duration Minutes', 'Note', 'Status']);
            foreach ($sessions as $session) {
                fputcsv($out, [$session->started_at?->toDateString(), $session->project?->name, $session->task?->title, $session->started_at?->format('H:i'), $session->ended_at?->format('H:i'), $session->duration_minutes, $session->notes, $session->status]);
            }
            fclose($out);
        }, 'my-work-sessions.csv');
    }
}
