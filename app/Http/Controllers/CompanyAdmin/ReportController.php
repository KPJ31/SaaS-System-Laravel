<?php

namespace App\Http\Controllers\CompanyAdmin;

use App\Http\Controllers\CompanyAdmin\Concerns\HandlesCompanyAccess;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Feedback;
use App\Models\Invoice;
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

    public function show(string $report): View
    {
        return view('company-admin.reports.show', $this->reportPayload($report));
    }

    public function export(string $report): StreamedResponse
    {
        $payload = $this->reportPayload($report);
        $filename = 'elevanix-company-'.$report.'-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($payload): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, $payload['headers']);

            foreach ($payload['rows'] as $row) {
                fputcsv($out, $row);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function exportPdf(string $report)
    {
        $payload = $this->reportPayload($report);
        $filename = 'elevanix-company-'.$report.'-'.now()->format('Y-m-d').'.pdf';

        return Pdf::loadView('company-admin.reports.pdf', $payload)
            ->setPaper('a4', 'landscape')
            ->download($filename);
    }

    private function reportPayload(string $report): array
    {
        abort_unless(array_key_exists($report, $this->reportTitles()), 404);

        $companyId = $this->companyId();

        [$headers, $rows] = match ($report) {
            'employees' => [
                ['Name', 'Email', 'Job Title', 'Department', 'Status', 'Joined'],
                User::where('company_id', $companyId)->where('role', 'employee')->orderBy('name')->get()->map(fn (User $employee) => [
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
                User::withCount([
                    'assignedTasks',
                    'assignedTasks as completed_tasks_count' => fn ($query) => $query->where('status', 'completed'),
                ])->withSum('workSessions as work_minutes', 'duration_minutes')
                    ->where('company_id', $companyId)
                    ->where('role', 'employee')
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
                Client::where('company_id', $companyId)->orderBy('name')->get()->map(fn (Client $client) => [
                    $client->name,
                    $client->email ?? '-',
                    $client->phone ?? '-',
                    $client->company_name ?? '-',
                    ucfirst($client->status),
                ]),
            ],
            'projects', 'project-progress' => [
                ['Project', 'Client', 'Status', 'Priority', 'Budget', 'Progress', 'Due Date'],
                Project::with('client')->where('company_id', $companyId)->orderBy('name')->get()->map(fn (Project $project) => [
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
                Task::with(['project', 'assignee'])
                    ->where('company_id', $companyId)
                    ->when($report === 'overdue-tasks', fn ($query) => $query->whereDate('due_date', '<', today())->whereNotIn('status', ['completed', 'cancelled']))
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
                WorkSession::with(['user', 'project', 'task'])->where('company_id', $companyId)->latest('started_at')->limit(1000)->get()->map(fn (WorkSession $session) => [
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
                ProjectRequest::with('client')->where('company_id', $companyId)->latest()->get()->map(fn (ProjectRequest $request) => [
                    $request->title,
                    $request->client?->name ?? '-',
                    $request->service_type ?? '-',
                    str_replace('_', ' ', ucfirst($request->status)),
                    number_format((float) $request->estimated_budget, 2),
                    $request->expected_end_date?->format('Y-m-d') ?? '-',
                ]),
            ],
            'payments', 'revenue' => [
                ['Reference', 'Client', 'Project', 'Amount', 'Method', 'Status', 'Paid At'],
                Payment::with(['client', 'project'])->where('company_id', $companyId)->where('payment_type', 'client_project')->latest()->get()->map(fn (Payment $payment) => [
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
                Invoice::with(['client', 'project'])->where('company_id', $companyId)->latest()->get()->map(fn (Invoice $invoice) => [
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
                Feedback::with(['client', 'project'])->where('company_id', $companyId)->latest()->get()->map(fn (Feedback $feedback) => [
                    $feedback->client?->name ?? '-',
                    $feedback->project?->name ?? '-',
                    $feedback->rating.'/5',
                    ucfirst($feedback->status),
                    $feedback->message,
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
        ];
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
        ];
    }
}
