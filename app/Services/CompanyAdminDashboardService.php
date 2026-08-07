<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\LeaveRequest;
use App\Models\Payment;
use App\Models\Project;
use App\Models\ProjectRequest;
use App\Models\SubscriptionChangeRequest;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkSession;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CompanyAdminDashboardService
{
    private const EMPLOYEE_ROLE = 'employee';

    private const ACTIVE_PROJECT_STATUSES = ['active', 'in_progress', 'testing'];

    private const OPEN_TASK_STATUSES = ['todo', 'assigned', 'in_progress', 'paused', 'blocked', 'submitted', 'under_review'];

    private const REVIEW_TASK_STATUSES = ['submitted', 'under_review'];

    public function dataFor(User $admin): array
    {
        $companyId = (int) $admin->company_id;
        $today = today();
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $employeeQuery = User::where('company_id', $companyId)->where('role', self::EMPLOYEE_ROLE);
        $taskQuery = Task::where('company_id', $companyId);

        $taskStatusCounts = $this->taskStatusCounts($companyId);
        $projectStatusCounts = $this->statusCounts(Project::where('company_id', $companyId));

        return [
            'primaryKpis' => [
                ['label' => 'Total Employees', 'value' => (clone $employeeQuery)->count(), 'icon' => 'fa-users', 'tone' => 'blue', 'subtitle' => 'Employee accounts only'],
                ['label' => 'Active Employees', 'value' => (clone $employeeQuery)->where('status', 'active')->count(), 'icon' => 'fa-user-check', 'tone' => 'green', 'subtitle' => 'Can access employee workspace'],
                ['label' => 'Total Clients', 'value' => Client::where('company_id', $companyId)->count(), 'icon' => 'fa-handshake', 'tone' => 'primary', 'subtitle' => 'Company-owned clients'],
                ['label' => 'Active Projects', 'value' => Project::where('company_id', $companyId)->whereIn('status', self::ACTIVE_PROJECT_STATUSES)->count(), 'icon' => 'fa-diagram-project', 'tone' => 'green', 'subtitle' => 'Active delivery work'],
            ],
            'secondaryKpis' => [
                ['label' => 'Open Tasks', 'value' => (clone $taskQuery)->whereIn('status', self::OPEN_TASK_STATUSES)->count(), 'icon' => 'fa-list-check', 'tone' => 'blue'],
                ['label' => 'Overdue Tasks', 'value' => $this->overdueTasksQuery($companyId)->count(), 'icon' => 'fa-triangle-exclamation', 'tone' => 'danger'],
                ['label' => 'Pending Requests', 'value' => ProjectRequest::where('company_id', $companyId)->whereIn('status', ['pending', 'under_review'])->count(), 'icon' => 'fa-inbox', 'tone' => 'yellow'],
                ['label' => 'Pending Leave', 'value' => LeaveRequest::where('company_id', $companyId)->where('status', 'pending')->count(), 'icon' => 'fa-calendar-check', 'tone' => 'yellow'],
            ],
            'attendance' => $this->attendanceSnapshot($companyId),
            'teamWorkload' => $this->teamWorkload($companyId),
            'upcomingDeadlines' => $this->upcomingDeadlines($companyId),
            'pendingActions' => $this->pendingActions($companyId),
            'financeSnapshot' => $this->financeSnapshot($admin, $monthStart, $monthEnd),
            'recentActivity' => AuditLog::with('user')
                ->where('company_id', $companyId)
                ->latest()
                ->take(8)
                ->get(),
            'recentEmployees' => (clone $employeeQuery)
                ->latest()
                ->take(5)
                ->get(['id', 'name', 'email', 'avatar', 'job_title', 'department', 'status', 'join_date', 'created_at']),
            'recentClients' => Client::withCount('projects')
                ->where('company_id', $companyId)
                ->latest()
                ->take(5)
                ->get(),
            'monthWorkHours' => round(WorkSession::where('company_id', $companyId)->whereBetween('started_at', [$monthStart, $monthEnd])->sum('duration_minutes') / 60, 1),
            'chartData' => [
                'projectStatusLabels' => $projectStatusCounts->pluck('label')->values(),
                'projectStatusValues' => $projectStatusCounts->pluck('total')->values(),
                'taskStatusLabels' => $taskStatusCounts->pluck('label')->values(),
                'taskStatusValues' => $taskStatusCounts->pluck('total')->values(),
                'employeeHoursLabels' => $this->employeeHours($companyId, $monthStart, $monthEnd)->pluck('name')->values(),
                'employeeHoursValues' => $this->employeeHours($companyId, $monthStart, $monthEnd)->pluck('hours')->values(),
            ],
        ];
    }

    private function attendanceSnapshot(int $companyId): array
    {
        $today = today();
        $activeEmployees = User::where('company_id', $companyId)
            ->where('role', self::EMPLOYEE_ROLE)
            ->where('status', 'active')
            ->count();

        $trackedToday = Attendance::where('company_id', $companyId)
            ->whereDate('attendance_date', $today)
            ->count();

        return [
            ['label' => 'Checked In Today', 'value' => Attendance::where('company_id', $companyId)->whereDate('attendance_date', $today)->whereNotNull('check_in_time')->count(), 'status' => 'present'],
            ['label' => 'Late Today', 'value' => Attendance::where('company_id', $companyId)->whereDate('attendance_date', $today)->where('is_late', true)->count(), 'status' => 'late'],
            ['label' => 'On Leave Today', 'value' => Attendance::where('company_id', $companyId)->whereDate('attendance_date', $today)->where('status', 'on_leave')->count(), 'status' => 'on_leave'],
            ['label' => 'Not Checked In', 'value' => max(0, $activeEmployees - $trackedToday), 'status' => 'not_checked_in'],
        ];
    }

    private function teamWorkload(int $companyId): Collection
    {
        return User::where('company_id', $companyId)
            ->where('users.role', self::EMPLOYEE_ROLE)
            ->where('status', 'active')
            ->withCount([
                'assignedTasks as open_tasks_count' => fn ($query) => $query->where('company_id', $companyId)->whereIn('status', self::OPEN_TASK_STATUSES),
                'assignedTasks as in_progress_tasks_count' => fn ($query) => $query->where('company_id', $companyId)->where('status', 'in_progress'),
                'assignedTasks as overdue_tasks_count' => fn ($query) => $query->where('company_id', $companyId)->whereDate('due_date', '<', today())->whereNotIn('status', ['completed', 'cancelled']),
            ])
            ->orderByDesc('open_tasks_count')
            ->orderBy('name')
            ->take(8)
            ->get(['id', 'name', 'email', 'job_title', 'avatar']);
    }

    private function upcomingDeadlines(int $companyId): Collection
    {
        $taskDeadlines = Task::with(['project:id,name', 'assignee:id,name'])
            ->where('company_id', $companyId)
            ->whereNotNull('due_date')
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->whereDate('due_date', '<=', today()->addDays(14))
            ->orderBy('due_date')
            ->take(8)
            ->get()
            ->map(fn (Task $task): array => [
                'type' => 'Task',
                'title' => $task->title,
                'context' => $task->project?->name,
                'assignee' => $task->assignee?->name ?? 'Unassigned',
                'due_date' => $task->due_date,
                'status' => $task->status,
                'url' => route('company-admin.tasks.show', $task),
            ]);

        $projectDeadlines = Project::with('client:id,name')
            ->where('company_id', $companyId)
            ->whereNotNull('due_date')
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->whereDate('due_date', '<=', today()->addDays(14))
            ->orderBy('due_date')
            ->take(8)
            ->get()
            ->map(fn (Project $project): array => [
                'type' => 'Project',
                'title' => $project->name,
                'context' => $project->client?->name,
                'assignee' => 'Team',
                'due_date' => $project->due_date,
                'status' => $project->status,
                'url' => route('company-admin.projects.show', $project),
            ]);

        return $taskDeadlines
            ->merge($projectDeadlines)
            ->sortBy('due_date')
            ->take(8)
            ->values();
    }

    private function pendingActions(int $companyId): array
    {
        return [
            ['label' => 'Project requests waiting for review', 'count' => ProjectRequest::where('company_id', $companyId)->whereIn('status', ['pending', 'under_review'])->count(), 'url' => route('company-admin.project-requests.index', ['status' => 'pending']), 'icon' => 'fa-inbox'],
            ['label' => 'Leave requests pending approval', 'count' => LeaveRequest::where('company_id', $companyId)->where('status', 'pending')->count(), 'url' => route('company-admin.leave-requests.index', ['status' => 'pending']), 'icon' => 'fa-calendar-check'],
            ['label' => 'Tasks submitted for review', 'count' => Task::where('company_id', $companyId)->whereIn('status', self::REVIEW_TASK_STATUSES)->count(), 'url' => route('company-admin.tasks.index', ['status' => 'submitted']), 'icon' => 'fa-paper-plane'],
            ['label' => 'Overdue tasks', 'count' => $this->overdueTasksQuery($companyId)->count(), 'url' => route('company-admin.reports.show', ['report' => 'overdue-tasks']), 'icon' => 'fa-triangle-exclamation'],
            ['label' => 'Unpaid invoices', 'count' => Invoice::where('company_id', $companyId)->whereIn('status', ['sent', 'unpaid', 'overdue'])->count(), 'url' => route('company-admin.invoices.index'), 'icon' => 'fa-file-invoice-dollar'],
            ['label' => 'Payment proofs to verify', 'count' => Payment::where('company_id', $companyId)->where('payment_type', 'client_project')->whereIn('status', ['pending', 'requested', 'proof_submitted'])->count(), 'url' => route('company-admin.payments.index', ['status' => 'pending']), 'icon' => 'fa-money-check-dollar'],
            ['label' => 'Subscription change requests', 'count' => SubscriptionChangeRequest::where('company_id', $companyId)->whereIn('status', SubscriptionChangeRequest::ACTIVE_STATUSES)->count(), 'url' => route('company-admin.subscription.index'), 'icon' => 'fa-code-compare'],
        ];
    }

    private function financeSnapshot(User $admin, mixed $monthStart, mixed $monthEnd): array
    {
        $companyId = (int) $admin->company_id;
        $paidStatuses = ['paid', 'received', 'verified'];

        return [
            'currency' => $admin->company?->setting?->currency ?? 'USD',
            'month_revenue' => (float) Payment::where('company_id', $companyId)
                ->where('payment_type', 'client_project')
                ->whereIn('status', $paidStatuses)
                ->whereBetween('paid_at', [$monthStart, $monthEnd])
                ->sum('amount'),
            'unpaid_balance' => (float) Invoice::where('company_id', $companyId)
                ->whereNotIn('status', ['draft', 'cancelled', 'paid'])
                ->sum('balance_amount'),
            'overdue_invoices' => Invoice::where('company_id', $companyId)
                ->where('status', 'overdue')
                ->count(),
            'pending_payment_proofs' => Payment::where('company_id', $companyId)
                ->where('payment_type', 'client_project')
                ->whereIn('status', ['pending', 'requested', 'proof_submitted'])
                ->count(),
            'recent_invoices' => Invoice::with('client:id,name')
                ->where('company_id', $companyId)
                ->latest()
                ->take(5)
                ->get(),
        ];
    }

    private function employeeHours(int $companyId, mixed $monthStart, mixed $monthEnd): Collection
    {
        return User::where('users.company_id', $companyId)
            ->where('users.role', self::EMPLOYEE_ROLE)
            ->leftJoin('work_sessions', function ($join) use ($companyId, $monthStart, $monthEnd): void {
                $join->on('users.id', '=', 'work_sessions.user_id')
                    ->where('work_sessions.company_id', $companyId)
                    ->whereBetween('work_sessions.started_at', [$monthStart, $monthEnd]);
            })
            ->select('users.id', 'users.name', DB::raw('round(coalesce(sum(work_sessions.duration_minutes), 0) / 60, 1) as hours'))
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('hours')
            ->orderBy('users.name')
            ->take(8)
            ->get();
    }

    private function taskStatusCounts(int $companyId): Collection
    {
        $raw = Task::where('company_id', $companyId)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return collect([
            ['label' => 'Todo', 'total' => (int) (($raw['todo'] ?? 0) + ($raw['assigned'] ?? 0))],
            ['label' => 'In Progress', 'total' => (int) (($raw['in_progress'] ?? 0) + ($raw['paused'] ?? 0))],
            ['label' => 'Review', 'total' => (int) (($raw['submitted'] ?? 0) + ($raw['under_review'] ?? 0))],
            ['label' => 'Blocked', 'total' => (int) ($raw['blocked'] ?? 0)],
            ['label' => 'Completed', 'total' => (int) ($raw['completed'] ?? 0)],
            ['label' => 'Cancelled', 'total' => (int) ($raw['cancelled'] ?? 0)],
        ])->filter(fn (array $item): bool => $item['total'] > 0)->values();
    }

    private function statusCounts($query): Collection
    {
        return $query
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->orderBy('status')
            ->get()
            ->map(fn ($row): array => [
                'label' => str((string) $row->status)->replace('_', ' ')->title()->toString(),
                'total' => (int) $row->total,
            ]);
    }

    private function overdueTasksQuery(int $companyId)
    {
        return Task::where('company_id', $companyId)
            ->whereDate('due_date', '<', today())
            ->whereNotIn('status', ['completed', 'cancelled']);
    }
}
