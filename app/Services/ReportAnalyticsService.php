<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Client;
use App\Models\Feedback;
use App\Models\Invoice;
use App\Models\LeaveRequest;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkSession;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ReportAnalyticsService
{
    public const ACTIVE_PROJECT_STATUSES = ['active', 'in_progress', 'testing'];

    public function definitions(): array
    {
        return [
            'projects' => ['Project Performance', 'Project completion, deadlines, task counts and logged hours.', 'fa-diagram-project', 'Projects'],
            'project-performance' => ['Project Performance', 'Project completion, deadlines, task counts and logged hours.', 'fa-diagram-project', 'Projects'],
            'tasks' => ['Task Performance', 'Task workflow, assignees, due dates and review status.', 'fa-list-check', 'Tasks'],
            'task-performance' => ['Task Performance', 'Task workflow, assignees, due dates and review status.', 'fa-list-check', 'Tasks'],
            'employee-progress' => ['Employee Progress', 'Employee task, work-session, attendance and leave facts.', 'fa-user-check', 'Workforce'],
            'work-hours' => ['Worked Hours', 'Logged work by employee, project, task and date.', 'fa-clock', 'Workforce'],
            'attendance' => ['Attendance', 'Attendance records, net hours, late arrivals and early departures.', 'fa-calendar-days', 'Workforce'],
            'leave' => ['Leave', 'Leave request counts, approved days and request history.', 'fa-calendar-check', 'Workforce'],
            'financial-summary' => ['Financial Summary', 'Invoices, payments, paid totals and open balances.', 'fa-file-invoice-dollar', 'Finance'],
            'invoices' => ['Invoice Report', 'Invoice totals, paid amounts and balances.', 'fa-file-invoice-dollar', 'Finance'],
            'payments' => ['Payment Report', 'Client project payment records and verification status.', 'fa-credit-card', 'Finance'],
            'revenue' => ['Revenue Report', 'Recognized client project payment records.', 'fa-money-bill-trend-up', 'Finance'],
            'employees' => ['Employee Report', 'Employee account and status details.', 'fa-users', 'Workforce'],
            'clients' => ['Client Report', 'Client contact and organization details.', 'fa-handshake', 'Projects'],
            'project-requests' => ['Project Request Report', 'Client request status and budgets.', 'fa-inbox', 'Projects'],
            'overdue-tasks' => ['Overdue Task Report', 'Open tasks past their due date.', 'fa-triangle-exclamation', 'Tasks'],
            'activity-logs' => ['Activity Log Report', 'Company activity records and tracked actions.', 'fa-clipboard-list', 'Workforce'],
            'feedback' => ['Feedback Report', 'Client ratings and feedback status.', 'fa-star', 'Projects'],
        ];
    }

    public function payload(int $companyId, Request $request, string $report): array
    {
        $report = $this->normalizeReport($report);
        abort_unless(array_key_exists($report, $this->definitions()), 404);

        return match ($report) {
            'projects', 'project-performance' => $this->projectPerformance($companyId, $request, $report),
            'tasks', 'task-performance', 'overdue-tasks' => $this->taskPerformance($companyId, $request, $report),
            'employee-progress' => $this->employeeProgress($companyId, $request, $report),
            'work-hours' => $this->workedHours($companyId, $request, $report),
            'attendance' => $this->attendance($companyId, $request, $report),
            'leave' => $this->leave($companyId, $request, $report),
            'financial-summary', 'invoices', 'payments', 'revenue' => $this->finance($companyId, $request, $report),
            'employees' => $this->employees($companyId, $request, $report),
            'clients' => $this->clients($companyId, $request, $report),
            'project-requests' => $this->projectRequests($companyId, $request, $report),
            'activity-logs' => $this->activityLogs($companyId, $request, $report),
            'feedback' => $this->feedback($companyId, $request, $report),
        };
    }

    public function overview(int $companyId, Request $request): array
    {
        [$from, $to] = $this->dateRange($request);
        $currency = $this->currency($companyId);

        return [
            'from' => $from,
            'to' => $to,
            'currency' => $currency,
            'employeeCount' => User::where('company_id', $companyId)->where('role', 'employee')->count(),
            'clientCount' => Client::where('company_id', $companyId)->count(),
            'projectCount' => Project::where('company_id', $companyId)->count(),
            'taskCount' => Task::where('company_id', $companyId)->count(),
            'workMinutes' => WorkSession::where('company_id', $companyId)->whereBetween('started_at', [$from, $to])->sum('duration_minutes'),
            'revenue' => Payment::where('company_id', $companyId)->where('payment_type', 'client_project')->whereIn('status', InvoiceCalculator::PAID_PAYMENT_STATUSES)->whereBetween('paid_at', [$from, $to])->sum('amount'),
            'invoiceTotal' => Invoice::where('company_id', $companyId)->whereBetween('issue_date', [$from->toDateString(), $to->toDateString()])->sum('total'),
            'unpaidBalance' => Invoice::where('company_id', $companyId)->whereNotIn('status', ['draft', 'cancelled', 'paid'])->sum('balance_amount'),
        ];
    }

    public function employeePersonalPayload(int $companyId, int $userId, Request $request): array
    {
        [$from, $to] = $this->dateRange($request);
        $tasks = Task::with('project')->where('company_id', $companyId)->where('assignee_id', $userId);
        $sessions = WorkSession::with(['project', 'task'])->where('company_id', $companyId)->where('user_id', $userId)->whereBetween('started_at', [$from, $to]);
        $attendance = Attendance::where('company_id', $companyId)->where('user_id', $userId)->whereBetween('attendance_date', [$from->toDateString(), $to->toDateString()]);
        $leave = LeaveRequest::where('company_id', $companyId)->where('user_id', $userId)->whereDate('start_date', '<=', $to->toDateString())->whereDate('end_date', '>=', $from->toDateString());
        $workflow = app(TaskWorkflowService::class);

        $taskRows = (clone $tasks)->latest()->take(10)->get();
        $statusCounts = (clone $tasks)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return [
            'from' => $from,
            'to' => $to,
            'summary' => [
                ['label' => 'Assigned Tasks', 'value' => (clone $tasks)->count(), 'icon' => 'fa-list-check'],
                ['label' => 'Completed Tasks', 'value' => (clone $tasks)->where('status', 'completed')->count(), 'icon' => 'fa-check', 'tone' => 'green'],
                ['label' => 'Pending Review', 'value' => (clone $tasks)->whereIn('status', TaskWorkflowService::GROUPS['review'])->count(), 'icon' => 'fa-paper-plane', 'tone' => 'yellow'],
                ['label' => 'Overdue Tasks', 'value' => (clone $tasks)->whereDate('due_date', '<', today())->whereNotIn('status', TaskWorkflowService::TERMINAL_STATUSES)->count(), 'icon' => 'fa-triangle-exclamation', 'tone' => 'danger'],
                ['label' => 'Logged Hours', 'value' => $this->hours((clone $sessions)->sum('duration_minutes')), 'icon' => 'fa-clock', 'tone' => 'blue'],
                ['label' => 'Approved Leave Days', 'value' => (clone $leave)->where('status', 'approved')->sum('total_days'), 'icon' => 'fa-calendar-check'],
            ],
            'taskRows' => $taskRows,
            'sessions' => (clone $sessions)->latest('started_at')->take(10)->get(),
            'attendances' => (clone $attendance)->latest('attendance_date')->take(10)->get(),
            'leaves' => (clone $leave)->latest()->take(10)->get(),
            'chartData' => [
                'taskLabels' => collect(TaskWorkflowService::GROUPS)->keys()->map(fn ($group) => str_replace('_', ' ', ucfirst($group)))->values(),
                'taskValues' => collect(TaskWorkflowService::GROUPS)->map(fn ($statuses) => (int) collect($statuses)->sum(fn ($status) => $statusCounts[$status] ?? 0))->values(),
                'hourLabels' => $this->periodLabels($from, $to),
                'hourValues' => $this->hoursByPeriod((clone $sessions), $from, $to),
            ],
            'workflow' => $workflow,
        ];
    }

    private function projectPerformance(int $companyId, Request $request, string $report): array
    {
        [$from, $to] = $this->dateRange($request);
        $progress = app(ProjectProgressService::class);
        $base = Project::with(['client', 'manager'])->withCount(['tasks', 'tasks as completed_tasks_count' => fn ($query) => $query->where('status', 'completed')])
            ->where('company_id', $companyId)
            ->when($request->filled('project_id'), fn ($query) => $query->whereKey($request->integer('project_id')))
            ->when($request->filled('client_id'), fn ($query) => $query->where('client_id', $request->integer('client_id')))
            ->when($request->filled('manager_id'), fn ($query) => $query->where('manager_id', $request->integer('manager_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->search.'%'))
            ->whereBetween('created_at', [$from, $to]);

        $projects = (clone $base)->latest()->limit(500)->get();
        $projectIds = $projects->pluck('id');
        $hours = WorkSession::where('company_id', $companyId)->whereIn('project_id', $projectIds)->selectRaw('project_id, sum(duration_minutes) as minutes')->groupBy('project_id')->pluck('minutes', 'project_id');

        return $this->makePayload($companyId, $request, $report, ['Project', 'Client', 'Manager', 'Status', 'Progress', 'Tasks', 'Completed Tasks', 'Deadline', 'Logged Hours'], $projects->map(fn (Project $project) => [
            $project->name,
            $project->client?->name ?? 'Internal',
            $project->manager?->name ?? 'Unassigned',
            $this->status($project->status),
            $progress->calculate($project).'%',
            $project->tasks_count,
            $project->completed_tasks_count,
            $this->deadline($project->due_date, $project->status),
            $this->hours((int) ($hours[$project->id] ?? 0)),
        ]), [
            ['label' => 'Total Projects', 'value' => $projects->count(), 'icon' => 'fa-diagram-project'],
            ['label' => 'Active Projects', 'value' => $projects->whereIn('status', self::ACTIVE_PROJECT_STATUSES)->count(), 'icon' => 'fa-bolt', 'tone' => 'green'],
            ['label' => 'Completed Projects', 'value' => $projects->where('status', 'completed')->count(), 'icon' => 'fa-check'],
            ['label' => 'Overdue Projects', 'value' => $projects->filter(fn (Project $project) => $project->due_date?->isPast() && ! in_array($project->status, ['completed', 'cancelled'], true))->count(), 'icon' => 'fa-triangle-exclamation', 'tone' => 'danger'],
        ], [
            'labels' => $projects->groupBy('status')->keys()->map(fn ($status) => $this->status($status))->values(),
            'values' => $projects->groupBy('status')->map->count()->values(),
        ], $projects->map(fn (Project $project) => route('company-admin.projects.show', $project))->values());
    }

    private function taskPerformance(int $companyId, Request $request, string $report): array
    {
        [$from, $to] = $this->dateRange($request);
        $workflow = app(TaskWorkflowService::class);
        $base = Task::with(['project', 'assignee'])->where('company_id', $companyId)
            ->when($report === 'overdue-tasks', fn ($query) => $query->whereDate('due_date', '<', today())->whereNotIn('status', TaskWorkflowService::TERMINAL_STATUSES))
            ->when($request->filled('project_id'), fn ($query) => $query->where('project_id', $request->integer('project_id')))
            ->when($request->filled('employee_id'), fn ($query) => $query->where('assignee_id', $request->integer('employee_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('priority'), fn ($query) => $query->where('priority', $request->string('priority')->toString()))
            ->when($request->filled('search'), fn ($query) => $query->where('title', 'like', '%'.$request->search.'%'))
            ->whereBetween('created_at', [$from, $to]);

        $tasks = (clone $base)->latest()->limit(500)->get();
        $statusCounts = $tasks->groupBy('status');

        return $this->makePayload($companyId, $request, $report, ['Task', 'Project', 'Assignee', 'Priority', 'Status', 'Created', 'Due', 'Completed', 'Deadline Result'], $tasks->map(fn (Task $task) => [
            $task->title,
            $task->project?->name ?? '-',
            $task->assignee?->name ?? 'Unassigned',
            ucfirst($task->priority ?? 'medium'),
            $workflow->label($task->status),
            $task->created_at->format('Y-m-d'),
            $task->due_date?->format('Y-m-d') ?? '-',
            $task->completed_at?->format('Y-m-d') ?? '-',
            $this->taskDeadlineResult($task, $workflow),
        ]), [
            ['label' => 'Total Tasks', 'value' => $tasks->count(), 'icon' => 'fa-list-check'],
            ['label' => 'Completed', 'value' => $tasks->where('status', 'completed')->count(), 'icon' => 'fa-check', 'tone' => 'green'],
            ['label' => 'In Progress', 'value' => $tasks->whereIn('status', TaskWorkflowService::GROUPS['in_progress'])->count(), 'icon' => 'fa-bars-progress', 'tone' => 'blue'],
            ['label' => 'Pending Review', 'value' => $tasks->whereIn('status', TaskWorkflowService::GROUPS['review'])->count(), 'icon' => 'fa-paper-plane', 'tone' => 'yellow'],
            ['label' => 'Overdue', 'value' => $tasks->filter(fn (Task $task) => $workflow->isOverdue($task))->count(), 'icon' => 'fa-triangle-exclamation', 'tone' => 'danger'],
        ], [
            'labels' => collect(TaskWorkflowService::GROUPS)->keys()->map(fn ($group) => str_replace('_', ' ', ucfirst($group)))->values(),
            'values' => collect(TaskWorkflowService::GROUPS)->map(fn ($statuses) => collect($statuses)->sum(fn ($status) => $statusCounts->get($status)?->count() ?? 0))->values(),
        ], $tasks->map(fn (Task $task) => route('company-admin.tasks.show', $task))->values());
    }

    private function employeeProgress(int $companyId, Request $request, string $report): array
    {
        [$from, $to] = $this->dateRange($request);
        $employees = User::where('company_id', $companyId)->where('role', 'employee')
            ->when($request->filled('employee_id'), fn ($query) => $query->whereKey($request->integer('employee_id')))
            ->orderBy('name')
            ->get();

        return $this->makePayload($companyId, $request, $report, ['Employee', 'Assigned Tasks', 'Completed Tasks', 'In Progress', 'Pending Review', 'Overdue Tasks', 'Logged Hours', 'Attendance Days', 'Approved Leave Days'], $employees->map(function (User $employee) use ($companyId, $from, $to) {
            $tasks = Task::where('company_id', $companyId)->where('assignee_id', $employee->id);
            return [
                $employee->name,
                (clone $tasks)->count(),
                (clone $tasks)->where('status', 'completed')->count(),
                (clone $tasks)->whereIn('status', TaskWorkflowService::GROUPS['in_progress'])->count(),
                (clone $tasks)->whereIn('status', TaskWorkflowService::GROUPS['review'])->count(),
                (clone $tasks)->whereDate('due_date', '<', today())->whereNotIn('status', TaskWorkflowService::TERMINAL_STATUSES)->count(),
                $this->hours(WorkSession::where('company_id', $companyId)->where('user_id', $employee->id)->whereBetween('started_at', [$from, $to])->sum('duration_minutes')),
                Attendance::where('company_id', $companyId)->where('user_id', $employee->id)->whereBetween('attendance_date', [$from->toDateString(), $to->toDateString()])->whereNotNull('check_in_time')->count(),
                LeaveRequest::where('company_id', $companyId)->where('user_id', $employee->id)->where('status', 'approved')->whereDate('start_date', '<=', $to->toDateString())->whereDate('end_date', '>=', $from->toDateString())->sum('total_days'),
            ];
        }), [
            ['label' => 'Employees', 'value' => $employees->count(), 'icon' => 'fa-users'],
            ['label' => 'Completed Tasks', 'value' => Task::where('company_id', $companyId)->whereIn('assignee_id', $employees->pluck('id'))->where('status', 'completed')->count(), 'icon' => 'fa-check', 'tone' => 'green'],
            ['label' => 'Logged Hours', 'value' => $this->hours(WorkSession::where('company_id', $companyId)->whereIn('user_id', $employees->pluck('id'))->whereBetween('started_at', [$from, $to])->sum('duration_minutes')), 'icon' => 'fa-clock', 'tone' => 'blue'],
        ], null, $employees->map(fn (User $employee) => route('company-admin.reports.show', ['report' => 'employee-progress', 'employee_id' => $employee->id, 'date_from' => $from->toDateString(), 'date_to' => $to->toDateString()]))->values());
    }

    private function workedHours(int $companyId, Request $request, string $report): array
    {
        [$from, $to] = $this->dateRange($request);
        $query = WorkSession::with(['user', 'project', 'task'])->where('company_id', $companyId)
            ->whereBetween('started_at', [$from, $to])
            ->when($request->filled('employee_id'), fn ($query) => $query->where('user_id', $request->integer('employee_id')))
            ->when($request->filled('project_id'), fn ($query) => $query->where('project_id', $request->integer('project_id')))
            ->when($request->filled('task_id'), fn ($query) => $query->where('task_id', $request->integer('task_id')));
        $sessions = $query->latest('started_at')->limit(500)->get();

        return $this->makePayload($companyId, $request, $report, ['Employee', 'Project', 'Task', 'Date', 'Duration', 'Remarks'], $sessions->map(fn (WorkSession $session) => [
            $session->user?->name ?? '-',
            $session->project?->name ?? '-',
            $session->task?->title ?? '-',
            $session->started_at?->format('Y-m-d') ?? '-',
            $this->hours((int) $session->duration_minutes),
            $session->notes ?? '-',
        ]), [
            ['label' => 'Logged Hours', 'value' => $this->hours($sessions->sum('duration_minutes')), 'icon' => 'fa-clock', 'tone' => 'blue'],
            ['label' => 'Employees', 'value' => $sessions->pluck('user_id')->filter()->unique()->count(), 'icon' => 'fa-users'],
            ['label' => 'Projects', 'value' => $sessions->pluck('project_id')->filter()->unique()->count(), 'icon' => 'fa-diagram-project'],
        ], [
            'labels' => $this->periodLabels($from, $to),
            'values' => $this->hoursByPeriod($query, $from, $to),
        ]);
    }

    private function attendance(int $companyId, Request $request, string $report): array
    {
        [$from, $to] = $this->dateRange($request);
        $query = Attendance::with('user')->where('company_id', $companyId)
            ->whereBetween('attendance_date', [$from->toDateString(), $to->toDateString()])
            ->when($request->filled('employee_id'), fn ($query) => $query->where('user_id', $request->integer('employee_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()));
        $rows = $query->latest('attendance_date')->limit(500)->get();

        return $this->makePayload($companyId, $request, $report, ['Employee', 'Date', 'Check In', 'Check Out', 'Gross', 'Deduction', 'Net', 'Status'], $rows->map(fn (Attendance $attendance) => [
            $attendance->user?->name ?? '-',
            $attendance->attendance_date?->format('Y-m-d') ?? '-',
            $attendance->check_in_time?->format('H:i') ?? '-',
            $attendance->check_out_time?->format('H:i') ?? '-',
            $this->hours((int) $attendance->gross_minutes),
            $this->hours((int) $attendance->lunch_break_minutes),
            $this->hours((int) $attendance->net_work_minutes),
            $this->status($attendance->status),
        ]), [
            ['label' => 'Checked In Records', 'value' => $rows->whereNotNull('check_in_time')->count(), 'icon' => 'fa-calendar-check'],
            ['label' => 'Late Records', 'value' => $rows->where('is_late', true)->count(), 'icon' => 'fa-clock', 'tone' => 'yellow'],
            ['label' => 'Net Hours', 'value' => $this->hours($rows->sum('net_work_minutes')), 'icon' => 'fa-business-time', 'tone' => 'blue'],
        ]);
    }

    private function leave(int $companyId, Request $request, string $report): array
    {
        [$from, $to] = $this->dateRange($request);
        $query = LeaveRequest::with('user')->where('company_id', $companyId)
            ->whereDate('start_date', '<=', $to->toDateString())
            ->whereDate('end_date', '>=', $from->toDateString())
            ->when($request->filled('employee_id'), fn ($query) => $query->where('user_id', $request->integer('employee_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('leave_type'), fn ($query) => $query->where('leave_type', $request->string('leave_type')->toString()));
        $rows = $query->latest()->limit(500)->get();

        return $this->makePayload($companyId, $request, $report, ['Employee', 'Type', 'Start', 'End', 'Duration', 'Status', 'Submitted'], $rows->map(fn (LeaveRequest $leave) => [
            $leave->user?->name ?? '-',
            ucfirst($leave->leave_type),
            $leave->start_date->format('Y-m-d'),
            $leave->end_date->format('Y-m-d'),
            $leave->total_days.' day'.((float) $leave->total_days === 1.0 ? '' : 's'),
            $this->status($leave->status),
            $leave->created_at->format('Y-m-d'),
        ]), [
            ['label' => 'Pending Requests', 'value' => $rows->where('status', 'pending')->count(), 'icon' => 'fa-hourglass-half', 'tone' => 'yellow'],
            ['label' => 'Approved Requests', 'value' => $rows->where('status', 'approved')->count(), 'icon' => 'fa-check', 'tone' => 'green'],
            ['label' => 'Rejected Requests', 'value' => $rows->where('status', 'rejected')->count(), 'icon' => 'fa-xmark', 'tone' => 'danger'],
            ['label' => 'Approved Leave Days', 'value' => $rows->where('status', 'approved')->sum('total_days'), 'icon' => 'fa-calendar-days'],
        ]);
    }

    private function finance(int $companyId, Request $request, string $report): array
    {
        [$from, $to] = $this->dateRange($request);
        $currency = $this->currency($companyId);
        $invoiceQuery = Invoice::with(['client', 'project'])
            ->where('company_id', $companyId)
            ->whereDate('issue_date', '>=', $from->toDateString())
            ->whereDate('issue_date', '<=', $to->toDateString());
        $paymentQuery = Payment::with(['client', 'project', 'invoice'])->where('company_id', $companyId)->where('payment_type', 'client_project')->whereBetween('created_at', [$from, $to]);

        if ($report === 'payments' || $report === 'revenue') {
            $payments = $paymentQuery->when($report === 'revenue', fn ($query) => $query->whereIn('status', InvoiceCalculator::PAID_PAYMENT_STATUSES))->latest()->limit(500)->get();
            $rows = $payments->map(fn (Payment $payment) => [$payment->transaction_reference ?? 'Payment #'.$payment->id, $payment->invoice?->invoice_number ?? '-', $payment->client?->name ?? '-', $payment->project?->name ?? '-', $currency.' '.number_format((float) $payment->amount, 2), $this->status($payment->status), $payment->paid_at?->format('Y-m-d') ?? '-']);
            $headers = ['Reference', 'Invoice', 'Client', 'Project', 'Amount', 'Status', 'Paid At'];
        } else {
            $invoices = $invoiceQuery->latest()->limit(500)->get();
            $rows = $invoices->map(fn (Invoice $invoice) => [$invoice->invoice_number, $invoice->client?->name ?? '-', $invoice->project?->name ?? '-', $invoice->issue_date?->format('Y-m-d') ?? '-', $currency.' '.number_format((float) $invoice->total, 2), $currency.' '.number_format((float) $invoice->paid_amount, 2), $currency.' '.number_format((float) $invoice->balance_amount, 2), $this->status($invoice->status)]);
            $headers = ['Invoice', 'Client', 'Project', 'Issue Date', 'Total', 'Paid', 'Balance', 'Status'];
        }

        return $this->makePayload($companyId, $request, $report, $headers, $rows, [
            ['label' => 'Total Invoiced', 'value' => $currency.' '.number_format((float) (clone $invoiceQuery)->sum('total'), 2), 'icon' => 'fa-file-invoice-dollar'],
            ['label' => 'Total Paid', 'value' => $currency.' '.number_format((float) (clone $paymentQuery)->whereIn('status', InvoiceCalculator::PAID_PAYMENT_STATUSES)->sum('amount'), 2), 'icon' => 'fa-money-check-dollar', 'tone' => 'green'],
            ['label' => 'Open Balance', 'value' => $currency.' '.number_format((float) Invoice::where('company_id', $companyId)->whereNotIn('status', ['draft', 'cancelled', 'paid'])->sum('balance_amount'), 2), 'icon' => 'fa-scale-balanced', 'tone' => 'yellow'],
        ]);
    }

    private function employees(int $companyId, Request $request, string $report): array
    {
        [$from, $to] = $this->dateRange($request);
        $rows = User::where('company_id', $companyId)->where('role', 'employee')->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))->orderBy('name')->limit(500)->get()
            ->map(fn (User $employee) => [$employee->name, $employee->email, $employee->job_title ?? '-', $employee->department ?? '-', $this->status($employee->status), $employee->join_date?->format('Y-m-d') ?? '-']);

        return $this->makePayload($companyId, $request, $report, ['Name', 'Email', 'Job Title', 'Department', 'Status', 'Joined'], $rows);
    }

    private function clients(int $companyId, Request $request, string $report): array
    {
        $rows = Client::where('company_id', $companyId)->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))->orderBy('name')->limit(500)->get()
            ->map(fn (Client $client) => [$client->name, $client->email ?? '-', $client->phone ?? '-', $client->company_name ?? '-', $this->status($client->status)]);

        return $this->makePayload($companyId, $request, $report, ['Name', 'Email', 'Phone', 'Organization', 'Status'], $rows);
    }

    private function projectRequests(int $companyId, Request $request, string $report): array
    {
        [$from, $to] = $this->dateRange($request);
        $rows = \App\Models\ProjectRequest::with('client')->where('company_id', $companyId)->whereBetween('created_at', [$from, $to])->latest()->limit(500)->get()
            ->map(fn ($projectRequest) => [$projectRequest->title, $projectRequest->client?->name ?? '-', $projectRequest->service_type ?? '-', $this->status($projectRequest->status), number_format((float) $projectRequest->estimated_budget, 2), $projectRequest->expected_end_date?->format('Y-m-d') ?? '-']);

        return $this->makePayload($companyId, $request, $report, ['Request', 'Client', 'Service Type', 'Status', 'Budget', 'Expected End'], $rows);
    }

    private function activityLogs(int $companyId, Request $request, string $report): array
    {
        [$from, $to] = $this->dateRange($request);
        $rows = \App\Models\AuditLog::with('user')->where('company_id', $companyId)->whereBetween('created_at', [$from, $to])->latest()->limit(500)->get()
            ->map(fn ($log) => [$log->user?->name ?? 'System', $log->module ?? '-', $this->status($log->action), $log->description ?? '-', $log->created_at->format('Y-m-d H:i')]);

        return $this->makePayload($companyId, $request, $report, ['User', 'Module', 'Action', 'Description', 'Created'], $rows);
    }

    private function feedback(int $companyId, Request $request, string $report): array
    {
        [$from, $to] = $this->dateRange($request);
        $rows = Feedback::with(['client', 'project'])->where('company_id', $companyId)->whereBetween('created_at', [$from, $to])->latest()->limit(500)->get()
            ->map(fn (Feedback $feedback) => [$feedback->client?->name ?? '-', $feedback->project?->name ?? '-', $feedback->rating.'/5', $this->status($feedback->status), $feedback->message]);

        return $this->makePayload($companyId, $request, $report, ['Client', 'Project', 'Rating', 'Status', 'Message'], $rows);
    }

    private function makePayload(int $companyId, Request $request, string $report, array $headers, Collection $rows, array $summary = [], ?array $chartData = null, ?Collection $rowLinks = null): array
    {
        $definitions = $this->definitions();
        [$from, $to] = $this->dateRange($request);

        return [
            'report' => $report,
            'title' => $definitions[$report][0],
            'description' => $definitions[$report][1],
            'category' => $definitions[$report][3],
            'generatedAt' => now(),
            'from' => $from,
            'to' => $to,
            'headers' => $headers,
            'rows' => $rows,
            'summary' => $summary,
            'chartData' => $chartData,
            'rowLinks' => $rowLinks ?? collect(),
            'filters' => $this->activeFilters($request),
            'currency' => $this->currency($companyId),
        ];
    }

    private function normalizeReport(string $report): string
    {
        return match ($report) {
            'employee-performance' => 'employee-progress',
            default => $report,
        };
    }

    private function dateRange(Request $request): array
    {
        $from = $request->date_from ? Carbon::parse($request->date_from)->startOfDay() : now()->startOfMonth();
        $to = $request->date_to ? Carbon::parse($request->date_to)->endOfDay() : now()->endOfDay();

        return [$from, $to];
    }

    private function activeFilters(Request $request): array
    {
        return collect($request->only(['search', 'date_from', 'date_to', 'status', 'employee_id', 'project_id', 'client_id', 'manager_id', 'priority', 'task_id', 'leave_type']))
            ->filter(fn ($value) => filled($value))
            ->mapWithKeys(fn ($value, $key) => [str_replace('_', ' ', ucfirst($key)) => (string) $value])
            ->all();
    }

    private function currency(int $companyId): string
    {
        return \App\Models\CompanySetting::where('company_id', $companyId)->value('currency') ?? 'USD';
    }

    private function status(string $status): string
    {
        return str_replace('_', ' ', ucfirst($status));
    }

    private function hours(int|float|null $minutes): string
    {
        return number_format(((float) $minutes) / 60, 1);
    }

    private function deadline($dueDate, string $status): string
    {
        if (! $dueDate) {
            return '-';
        }

        $state = $dueDate->isPast() && ! in_array($status, ['completed', 'cancelled'], true) ? 'Overdue' : 'Open';

        return $dueDate->format('Y-m-d').' ('.$state.')';
    }

    private function taskDeadlineResult(Task $task, TaskWorkflowService $workflow): string
    {
        if ($task->status === 'completed' && $task->completed_at && $task->due_date) {
            return $task->completed_at->toDateString() <= $task->due_date->toDateString() ? 'On Time' : 'Late';
        }

        if ($workflow->isOverdue($task)) {
            return 'Overdue';
        }

        return $task->status === 'completed' ? 'Completed' : 'Open';
    }

    private function periodLabels(Carbon $from, Carbon $to): Collection
    {
        $days = $from->diffInDays($to);
        $interval = $days > 90 ? '1 month' : ($days > 31 ? '1 week' : '1 day');

        return collect(CarbonPeriod::create($from->copy()->startOfDay(), $interval, $to->copy()->startOfDay()))
            ->map(fn (Carbon $date) => $interval === '1 month' ? $date->format('M Y') : $date->format('M d'))
            ->values();
    }

    private function hoursByPeriod($query, Carbon $from, Carbon $to): Collection
    {
        $labels = $this->periodLabels($from, $to);

        return $labels->map(function (string $label) use ($query): float {
            $periodQuery = clone $query;

            if (str_contains($label, ' ')) {
                $date = Carbon::parse($label);
                return round((float) $periodQuery->whereDate('started_at', $date->toDateString())->sum('duration_minutes') / 60, 2);
            }

            return 0.0;
        });
    }
}
