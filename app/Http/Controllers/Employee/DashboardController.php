<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\CompanySetting;
use App\Models\LeaveRequest;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkSession;
use Carbon\CarbonPeriod;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();
        $tasksQuery = Task::where('company_id', $user->company_id)->where('assignee_id', $user->id);
        $sessionsQuery = WorkSession::where('company_id', $user->company_id)->where('user_id', $user->id);
        $projectIds = (clone $tasksQuery)->pluck('project_id')->merge($user->projects()->pluck('projects.id'))->unique();
        $weeklyLabels = [];
        $weeklyHours = [];
        foreach (CarbonPeriod::create(now()->startOfWeek(), now()->endOfWeek()) as $date) {
            $weeklyLabels[] = $date->format('D');
            $weeklyHours[] = round((clone $sessionsQuery)->whereDate('started_at', $date)->sum('duration_minutes') / 60, 2);
        }
        $statusCounts = (clone $tasksQuery)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $totalTasks = (clone $tasksQuery)->count();
        $completedTasks = (clone $tasksQuery)->where('status', 'completed')->count();
        $score = $totalTasks > 0 ? (int) round(($completedTasks / $totalTasks) * 40 + min(30, ((clone $sessionsQuery)->whereNotNull('ended_at')->count() / max(1, now()->day)) * 30) + 20) : null;
        $setting = CompanySetting::firstOrCreate(
            ['company_id' => $user->company_id],
            ['timezone' => $user->company?->timezone ?? 'UTC', 'currency' => 'USD', 'settings' => []]
        );
        $attendanceSettings = $setting->attendanceSettings() + ['timezone' => $setting->timezone ?: 'UTC'];
        $today = now($attendanceSettings['timezone'])->toDateString();
        $attendance = Attendance::where('company_id', $user->company_id)->where('user_id', $user->id)->whereDate('attendance_date', $today)->first();
        $todayTaskMinutes = (clone $sessionsQuery)->whereDate('started_at', $today)->sum('duration_minutes');

        return view('employee.dashboard', [
            'stats' => [
                'totalProjects' => $projectIds->count(),
                'activeProjects' => Project::whereIn('id', $projectIds)->whereIn('status', ['active', 'in_progress', 'testing'])->count(),
                'completedProjects' => Project::whereIn('id', $projectIds)->where('status', 'completed')->count(),
                'totalTasks' => $totalTasks,
                'pendingTasks' => (clone $tasksQuery)->whereIn('status', ['todo', 'assigned'])->count(),
                'inProgressTasks' => (clone $tasksQuery)->where('status', 'in_progress')->count(),
                'submittedTasks' => (clone $tasksQuery)->whereIn('status', ['submitted', 'under_review'])->count(),
                'completedTasks' => $completedTasks,
                'overdueTasks' => (clone $tasksQuery)->whereDate('due_date', '<', today())->whereNotIn('status', ['completed', 'cancelled'])->count(),
                'todayHours' => round((clone $sessionsQuery)->whereDate('started_at', today())->sum('duration_minutes') / 60, 2),
                'weekHours' => round((clone $sessionsQuery)->whereBetween('started_at', [now()->startOfWeek(), now()->endOfWeek()])->sum('duration_minutes') / 60, 2),
                'monthHours' => round((clone $sessionsQuery)->whereMonth('started_at', now()->month)->whereYear('started_at', now()->year)->sum('duration_minutes') / 60, 2),
                'pendingLeaves' => LeaveRequest::where('company_id', $user->company_id)->where('user_id', $user->id)->where('status', 'pending')->count(),
                'approvedLeaves' => LeaveRequest::where('company_id', $user->company_id)->where('user_id', $user->id)->where('status', 'approved')->count(),
                'performanceScore' => $score,
                'companyTotalProjects' => $user->can('projects.view') ? Project::where('company_id', $user->company_id)->count() : null,
                'companyActiveProjects' => $user->can('projects.view') ? Project::where('company_id', $user->company_id)->whereIn('status', ['active', 'in_progress', 'testing'])->count() : null,
                'companyCompletedProjects' => $user->can('projects.view') ? Project::where('company_id', $user->company_id)->where('status', 'completed')->count() : null,
                'companyTotalEmployees' => $user->can('employees.view') ? User::where('company_id', $user->company_id)->where('role', 'employee')->count() : null,
                'companyActiveEmployees' => $user->can('employees.view') ? User::where('company_id', $user->company_id)->where('role', 'employee')->where('status', 'active')->count() : null,
                'companyTotalTasks' => $user->can('tasks.view') ? Task::where('company_id', $user->company_id)->count() : null,
                'companyPendingTasks' => $user->can('tasks.view') ? Task::where('company_id', $user->company_id)->whereIn('status', ['todo', 'assigned'])->count() : null,
                'companyOverdueTasks' => $user->can('tasks.view') ? Task::where('company_id', $user->company_id)->whereDate('due_date', '<', today())->whereNotIn('status', ['completed', 'cancelled'])->count() : null,
                'companyClients' => $user->can('clients.view') ? Client::where('company_id', $user->company_id)->count() : null,
                'pendingPayments' => $user->can('payments.view') ? Payment::where('company_id', $user->company_id)->whereIn('status', ['pending', 'requested', 'proof_submitted'])->count() : null,
                'paidPayments' => $user->can('payments.view') ? Payment::where('company_id', $user->company_id)->whereIn('status', ['paid', 'received', 'verified'])->count() : null,
            ],
            'tasks' => (clone $tasksQuery)->with('project')->latest()->take(8)->get(),
            'todayTasks' => (clone $tasksQuery)->with('project')->whereDate('due_date', today())->latest()->take(6)->get(),
            'overdueTasks' => (clone $tasksQuery)->with('project')->whereDate('due_date', '<', today())->whereNotIn('status', ['completed', 'cancelled'])->latest()->take(6)->get(),
            'sessions' => (clone $sessionsQuery)->with(['project', 'task'])->latest()->take(5)->get(),
            'activeTimer' => (clone $sessionsQuery)->with(['project', 'task'])->where('status', 'running')->whereNull('ended_at')->latest()->first(),
            'attendance' => $attendance,
            'attendanceSettings' => $attendanceSettings,
            'attendanceSummary' => [
                'todayTaskMinutes' => $todayTaskMinutes,
                'unallocatedMinutes' => $attendance ? max(0, (int) $attendance->net_work_minutes - (int) $todayTaskMinutes) : 0,
            ],
            'notifications' => $user->notifications()->latest()->take(5)->get(),
            'activities' => AuditLog::where('company_id', $user->company_id)->where('user_id', $user->id)->latest()->take(6)->get(),
            'chartData' => [
                'weeklyLabels' => $weeklyLabels,
                'weeklyHours' => $weeklyHours,
                'taskStatusLabels' => $statusCounts->keys()->map(fn ($status) => str_replace('_', ' ', $status))->values(),
                'taskStatusValues' => $statusCounts->values(),
            ],
        ]);
    }
}
