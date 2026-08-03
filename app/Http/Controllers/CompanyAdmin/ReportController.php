<?php

namespace App\Http\Controllers\CompanyAdmin;

use App\Http\Controllers\CompanyAdmin\Concerns\HandlesCompanyAccess;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Feedback;
use App\Models\Invoice;
use App\Models\LeaveRequest;
use App\Models\Payment;
use App\Models\Project;
use App\Models\ProjectRequest;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkSession;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    use HandlesCompanyAccess;

    public function index(Request $request): View
    {
        $from = $request->date_from ? Carbon::parse($request->date_from)->startOfDay() : now()->startOfMonth();
        $to = $request->date_to ? Carbon::parse($request->date_to)->endOfDay() : now()->endOfDay();

        return view('company-admin.reports.index', [
            'from' => $from,
            'to' => $to,
            'employeeCount' => User::where('company_id', $this->companyId())->where('role', 'employee')->count(),
            'clientCount' => Client::where('company_id', $this->companyId())->count(),
            'projectCount' => Project::where('company_id', $this->companyId())->count(),
            'taskCount' => Task::where('company_id', $this->companyId())->count(),
            'workMinutes' => WorkSession::where('company_id', $this->companyId())->whereBetween('started_at', [$from, $to])->sum('duration_minutes'),
            'revenue' => Payment::where('company_id', $this->companyId())->where('payment_type', 'client_project')->whereIn('status', ['paid', 'received', 'verified'])->whereBetween('created_at', [$from, $to])->sum('amount'),
            'invoiceTotal' => Invoice::where('company_id', $this->companyId())->whereBetween('created_at', [$from, $to])->sum('total'),
            'feedbackCount' => Feedback::where('company_id', $this->companyId())->whereBetween('created_at', [$from, $to])->count(),
            'requestCount' => ProjectRequest::where('company_id', $this->companyId())->whereBetween('created_at', [$from, $to])->count(),
        ]);
    }

    public function show(Request $request, string $report): View
    {
        return view('company-admin.reports.show', $this->reportPayload($request, $report));
    }

    public function export(Request $request, string $report): StreamedResponse
    {
        $payload = $this->reportPayload($request, $report);
        $filename = 'elevanix-company-'.$report.'-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($payload): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, $this->safeCsvRow([$payload['title']]));
            fputcsv($out, $this->safeCsvRow(['Company', $payload['company']->name]));
            fputcsv($out, $this->safeCsvRow(['Generated At', $payload['generatedAt']->format('Y-m-d H:i')]));
            foreach ($payload['filters'] as $label => $value) {
                fputcsv($out, $this->safeCsvRow([$label, $value]));
            }
            fputcsv($out, []);
            fputcsv($out, $this->safeCsvRow($payload['headers']));

            foreach ($payload['rows'] as $row) {
                fputcsv($out, $this->safeCsvRow($row));
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function exportPdf(Request $request, string $report)
    {
        $payload = $this->reportPayload($request, $report);
        $filename = 'elevanix-company-'.$report.'-'.now()->format('Y-m-d').'.pdf';

        return Pdf::loadView('company-admin.reports.pdf', $payload)
            ->setPaper('a4', 'landscape')
            ->download($filename);
    }

    private function reportPayload(Request $request, string $report): array
    {
        abort_unless(array_key_exists($report, $this->reportTitles()), 404);
        $this->authorizeReportAccess($report, str_contains($request->route()?->getName() ?? '', '.export') || str_contains($request->route()?->getName() ?? '', '.pdf'));
        $this->authorizeReportFilters($request);

        $companyId = $this->companyId();

        [$headers, $rows] = match ($report) {
            'employees' => [
                ['Name', 'Email', 'Job Title', 'Department', 'Status', 'Joined'],
                $this->applyCommonFilters(User::where('company_id', $companyId)->where('role', 'employee'), $request, ['name', 'email', 'job_title', 'department'])
                    ->orderBy('name')
                    ->get()
                    ->map(fn (User $employee) => [
                    $employee->name,
                    $employee->email,
                    $employee->job_title ?? '-',
                    $employee->department ?? '-',
                    ucfirst($employee->status),
                    $employee->join_date?->format('Y-m-d') ?? '-',
                ]),
            ],
            'employee-performance' => [
                ['Employee', 'Completed Tasks', 'Total Tasks', 'Work Hours', 'Performance'],
                $this->applyCommonFilters(User::withCount([
                    'assignedTasks',
                    'assignedTasks as completed_tasks_count' => fn ($query) => $query->where('status', 'completed'),
                ])->withSum('workSessions as work_minutes', 'duration_minutes')
                    ->where('company_id', $companyId)
                    ->where('role', 'employee'), $request, ['name', 'email'])
                    ->orderBy('name')
                    ->get()
                    ->map(fn (User $employee) => [
                        $employee->name,
                        $employee->completed_tasks_count,
                        $employee->assigned_tasks_count,
                        number_format(($employee->work_minutes ?? 0) / 60, 1),
                        $employee->assigned_tasks_count > 0 ? number_format(($employee->completed_tasks_count / $employee->assigned_tasks_count) * 100, 0).'%' : 'Not enough data',
                    ]),
            ],
            'clients' => [
                ['Name', 'Email', 'Phone', 'Organization', 'Status'],
                $this->applyCommonFilters(Client::where('company_id', $companyId), $request, ['name', 'email', 'phone', 'company_name'])
                    ->orderBy('name')
                    ->get()
                    ->map(fn (Client $client) => [
                    $client->name,
                    $client->email ?? '-',
                    $client->phone ?? '-',
                    $client->company_name ?? '-',
                    ucfirst($client->status),
                ]),
            ],
            'projects', 'project-progress' => [
                ['Project', 'Client', 'Status', 'Priority', 'Budget', 'Progress', 'Due Date'],
                $this->applyCommonFilters(Project::with('client')->where('company_id', $companyId), $request, ['name', 'priority'])
                    ->orderBy('name')
                    ->get()
                    ->map(fn (Project $project) => [
                    $project->name,
                    $project->client?->name ?? 'Internal',
                    str_replace('_', ' ', ucfirst($project->status)),
                    ucfirst($project->priority ?? 'medium'),
                    number_format((float) $project->budget, 2),
                    $project->progress.'%',
                    $project->due_date?->format('Y-m-d') ?? '-',
                ]),
            ],
            'tasks', 'overdue-tasks' => [
                ['Task', 'Project', 'Assignee', 'Priority', 'Status', 'Due Date'],
                $this->applyCommonFilters(Task::with(['project', 'assignee'])
                    ->where('company_id', $companyId)
                    ->when($report === 'overdue-tasks', fn ($query) => $query->whereDate('due_date', '<', today())->whereNotIn('status', ['completed', 'cancelled'])), $request, ['title', 'priority'])
                    ->when($request->filled('employee_id'), fn ($query) => $query->where('assignee_id', $request->integer('employee_id')))
                    ->latest()
                    ->get()
                    ->map(fn (Task $task) => [
                        $task->title,
                        $task->project?->name ?? '-',
                        $task->assignee?->name ?? 'Unassigned',
                        ucfirst($task->priority),
                        str_replace('_', ' ', ucfirst($task->status)),
                        $task->due_date?->format('Y-m-d') ?? '-',
                    ]),
            ],
            'work-hours' => [
                ['Employee', 'Project', 'Task', 'Started', 'Ended', 'Hours'],
                $this->applyCommonFilters(WorkSession::with(['user', 'project', 'task'])->where('company_id', $companyId), $request, ['notes'], 'started_at')
                    ->when($request->filled('employee_id'), fn ($query) => $query->where('user_id', $request->integer('employee_id')))
                    ->latest('started_at')
                    ->limit(1000)
                    ->get()
                    ->map(fn (WorkSession $session) => [
                    $session->user?->name ?? '-',
                    $session->project?->name ?? '-',
                    $session->task?->title ?? '-',
                    $session->started_at?->format('Y-m-d H:i') ?? '-',
                    $session->ended_at?->format('Y-m-d H:i') ?? 'Running',
                    number_format($session->duration_minutes / 60, 1),
                ]),
            ],
            'project-requests' => [
                ['Request', 'Client', 'Service Type', 'Status', 'Budget', 'Expected End'],
                $this->applyCommonFilters(ProjectRequest::with('client')->where('company_id', $companyId), $request, ['title', 'service_type'])
                    ->latest()
                    ->get()
                    ->map(fn (ProjectRequest $projectRequest) => [
                    $projectRequest->title,
                    $projectRequest->client?->name ?? '-',
                    $projectRequest->service_type ?? '-',
                    str_replace('_', ' ', ucfirst($projectRequest->status)),
                    number_format((float) $projectRequest->estimated_budget, 2),
                    $projectRequest->expected_end_date?->format('Y-m-d') ?? '-',
                ]),
            ],
            'payments', 'revenue' => [
                ['Reference', 'Client', 'Project', 'Amount', 'Method', 'Status', 'Paid At'],
                $this->applyCommonFilters(Payment::with(['client', 'project'])->where('company_id', $companyId)->where('payment_type', 'client_project'), $request, ['transaction_reference', 'method'])
                    ->latest()
                    ->get()
                    ->map(fn (Payment $payment) => [
                    $payment->transaction_reference ?? 'Payment #'.$payment->id,
                    $payment->client?->name ?? '-',
                    $payment->project?->name ?? '-',
                    number_format((float) $payment->amount, 2),
                    str_replace('_', ' ', $payment->method),
                    str_replace('_', ' ', ucfirst($payment->status)),
                    $payment->paid_at?->format('Y-m-d') ?? '-',
                ]),
            ],
            'invoices' => [
                ['Invoice', 'Client', 'Project', 'Issue Date', 'Due Date', 'Total', 'Status'],
                $this->applyCommonFilters(Invoice::with(['client', 'project'])->where('company_id', $companyId), $request, ['invoice_number'])
                    ->latest()
                    ->get()
                    ->map(fn (Invoice $invoice) => [
                    $invoice->invoice_number,
                    $invoice->client?->name ?? '-',
                    $invoice->project?->name ?? '-',
                    $invoice->issue_date?->format('Y-m-d') ?? '-',
                    $invoice->due_date?->format('Y-m-d') ?? '-',
                    number_format((float) $invoice->total, 2),
                    str_replace('_', ' ', ucfirst($invoice->status)),
                ]),
            ],
            'feedback' => [
                ['Client', 'Project', 'Rating', 'Status', 'Message'],
                $this->applyCommonFilters(Feedback::with(['client', 'project'])->where('company_id', $companyId), $request, ['message'])
                    ->latest()
                    ->get()
                    ->map(fn (Feedback $feedback) => [
                    $feedback->client?->name ?? '-',
                    $feedback->project?->name ?? '-',
                    $feedback->rating.'/5',
                    ucfirst($feedback->status),
                    $feedback->message,
                ]),
            ],
            'leave' => [
                ['Employee', 'Type', 'Start', 'End', 'Days', 'Status', 'Reviewed By'],
                $this->applyCommonFilters(LeaveRequest::with(['user', 'reviewer'])->where('company_id', $companyId), $request, ['leave_type', 'reason'], 'start_date')
                    ->when($request->filled('employee_id'), fn ($query) => $query->where('user_id', $request->integer('employee_id')))
                    ->latest()
                    ->get()
                    ->map(fn (LeaveRequest $leave) => [
                    $leave->user?->name ?? '-',
                    ucfirst($leave->leave_type),
                    $leave->start_date->format('Y-m-d'),
                    $leave->end_date->format('Y-m-d'),
                    $leave->total_days,
                    ucfirst($leave->status),
                    $leave->reviewer?->name ?? '-',
                ]),
            ],
            'attendance' => [
                ['Employee', 'Date', 'Check In', 'Check Out', 'Net Minutes', 'Status', 'Late Minutes', 'Early Departure', 'Note'],
                $this->applyCommonFilters(Attendance::with('user')->where('company_id', $companyId), $request, ['status', 'note'], 'attendance_date')
                    ->when($request->filled('employee_id'), fn ($query) => $query->where('user_id', $request->integer('employee_id')))
                    ->latest('attendance_date')
                    ->get()
                    ->map(fn (Attendance $attendance) => [
                    $attendance->user?->name ?? '-',
                    $attendance->attendance_date?->format('Y-m-d') ?? '-',
                    $attendance->check_in_time?->format('H:i') ?? '-',
                    $attendance->check_out_time?->format('H:i') ?? '-',
                    $attendance->net_work_minutes,
                    str_replace('_', ' ', ucfirst($attendance->status)),
                    $attendance->late_minutes,
                    $attendance->early_departure_minutes,
                    $attendance->note ?? '-',
                ]),
            ],
            'activity-logs' => [
                ['User', 'Module', 'Action', 'Description', 'Created'],
                $this->applyCommonFilters(AuditLog::with('user')->where('company_id', $companyId), $request, ['module', 'action', 'description'], 'created_at', false)
                    ->latest()
                    ->limit(500)
                    ->get()
                    ->map(fn (AuditLog $log) => [
                    $log->user?->name ?? 'System',
                    $log->module ?? '-',
                    str_replace('_', ' ', $log->action),
                    $log->description ?? '-',
                    $log->created_at->format('Y-m-d H:i'),
                ]),
            ],
        };

        return [
            'report' => $report,
            'title' => $this->reportTitles()[$report],
            'company' => $this->company(),
            'generatedAt' => now(),
            'headers' => $headers,
            'rows' => $rows,
            'filters' => $this->activeFilters($request),
            'employees' => User::where('company_id', $companyId)->where('role', 'employee')->orderBy('name')->get(),
        ];
    }

    private function applyCommonFilters($query, Request $request, array $searchColumns = [], string $dateColumn = 'created_at', bool $filterStatus = true)
    {
        $query
            ->when($filterStatus && $request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate($dateColumn, '>=', Carbon::parse($request->date_from)->toDateString()))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate($dateColumn, '<=', Carbon::parse($request->date_to)->toDateString()));

        if ($request->filled('search') && $searchColumns !== []) {
            $search = $request->string('search')->toString();
            $query->where(function ($query) use ($searchColumns, $search): void {
                foreach ($searchColumns as $column) {
                    $query->orWhere($column, 'like', '%'.$search.'%');
                }
            });
        }

        return $query;
    }

    private function activeFilters(Request $request): array
    {
        return collect($request->only(['search', 'date_from', 'date_to', 'status', 'employee_id']))
            ->filter(fn ($value) => filled($value))
            ->mapWithKeys(fn ($value, $key) => [str_replace('_', ' ', ucfirst($key)) => (string) $value])
            ->all();
    }

    private function authorizeReportFilters(Request $request): void
    {
        if ($request->filled('employee_id')) {
            abort_unless(User::where('company_id', $this->companyId())->where('role', 'employee')->whereKey($request->integer('employee_id'))->exists(), 403);
        }
    }

    private function authorizeReportAccess(string $report, bool $exporting): void
    {
        $user = auth()->user();

        if ($user->role === 'company_admin') {
            return;
        }

        abort_unless($user->can($exporting ? 'reports.export' : 'reports.view'), 403);

        $requiredPermission = [
            'employees' => 'employees.view',
            'employee-performance' => 'employees.view',
            'projects' => 'projects.view',
            'project-progress' => 'projects.view',
            'project-requests' => 'project-requests.view',
            'tasks' => 'tasks.view',
            'overdue-tasks' => 'tasks.view',
            'work-hours' => 'work-sessions.view-all',
            'clients' => 'clients.view',
            'payments' => 'payments.view',
            'revenue' => 'payments.view',
            'invoices' => 'invoices.view',
            'feedback' => 'feedback.view',
            'leave' => 'leave-requests.view-all',
            'attendance' => 'attendance.view-all',
            'activity-logs' => 'activity-logs.view',
        ][$report] ?? null;

        abort_unless($requiredPermission === null || $user->can($requiredPermission), 403);
    }

    private function reportTitles(): array
    {
        return [
            'employees' => 'Employee Report',
            'employee-performance' => 'Employee Performance Report',
            'projects' => 'Project Report',
            'project-progress' => 'Project Progress Report',
            'tasks' => 'Task Report',
            'overdue-tasks' => 'Overdue Task Report',
            'work-hours' => 'Work Hour Report',
            'clients' => 'Client Report',
            'project-requests' => 'Project Request Report',
            'payments' => 'Payment Report',
            'invoices' => 'Invoice Report',
            'revenue' => 'Revenue Report',
            'feedback' => 'Feedback Report',
            'leave' => 'Leave Report',
            'attendance' => 'Attendance Report',
            'activity-logs' => 'Activity Log Report',
        ];
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
