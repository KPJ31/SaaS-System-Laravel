<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Task;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        return view('super-admin.reports.index', [
            'companies' => Company::orderBy('name')->get(),
            'plans' => SubscriptionPlan::orderBy('name')->get(),
            'companyCount' => Company::count(),
            'activeCompanyCount' => Company::where('status', 'active')->count(),
            'suspendedCompanyCount' => Company::where('status', 'suspended')->count(),
            'subscriptionCount' => Subscription::count(),
            'revenueTotal' => Payment::where('payment_type', 'subscription')->whereIn('status', ['verified', 'received', 'paid'])->sum('amount'),
            'userCount' => User::count(),
            'expiringSoonCount' => Subscription::whereDate('renews_at', '<=', now()->addDays(30))->whereDate('renews_at', '>=', now())->count(),
            'auditCount' => AuditLog::count(),
        ]);
    }

    public function show(Request $request, string $report): View
    {
        return view('super-admin.reports.show', $this->reportPayload($request, $report));
    }

    public function export(Request $request, string $report): StreamedResponse
    {
        $payload = $this->reportPayload($request, $report);

        $filename = 'elevanix-'.$report.'-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($payload): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, $this->safeCsvRow([$payload['title']]));
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
        $filename = 'elevanix-'.$report.'-'.now()->format('Y-m-d').'.pdf';

        return Pdf::loadView('super-admin.reports.pdf', $payload)
            ->setPaper('a4', 'landscape')
            ->download($filename);
    }

    private function reportPayload(Request $request, string $report): array
    {
        abort_unless(array_key_exists($report, $this->reportTitles()), 404);

        [$headers, $rows] = match ($report) {
            'companies' => [
                ['Name', 'Email', 'Status', 'Created'],
                $this->applyCommonFilters(Company::query(), $request, ['name', 'email'])
                    ->orderBy('name')
                    ->get()
                    ->map(fn ($company) => [
                    $company->name,
                    $company->email,
                    ucfirst($company->status),
                    $company->created_at->format('Y-m-d'),
                ]),
            ],
            'subscriptions' => [
                ['Company', 'Plan', 'Status', 'Starts', 'Renews', 'Ends'],
                $this->applyCommonFilters(Subscription::with(['company', 'plan']), $request)
                    ->when($request->filled('company_id'), fn ($query) => $query->where('company_id', $request->integer('company_id')))
                    ->when($request->filled('plan_id'), fn ($query) => $query->where('subscription_plan_id', $request->integer('plan_id')))
                    ->latest()
                    ->get()
                    ->map(fn ($subscription) => [
                    $subscription->company?->name ?? '-',
                    $subscription->plan?->name ?? '-',
                    ucfirst($subscription->status),
                    $subscription->starts_at?->format('Y-m-d') ?? '-',
                    $subscription->renews_at?->format('Y-m-d') ?? '-',
                    $subscription->ends_at?->format('Y-m-d') ?? '-',
                ]),
            ],
            'payments' => [
                ['Reference', 'Company', 'Amount', 'Method', 'Status', 'Paid At'],
                $this->applyCommonFilters(Payment::with('company')->where('payment_type', 'subscription'), $request, ['transaction_reference', 'method'])
                    ->when($request->filled('company_id'), fn ($query) => $query->where('company_id', $request->integer('company_id')))
                    ->latest()
                    ->get()
                    ->map(fn ($payment) => [
                    $payment->transaction_reference ?? 'Payment #'.$payment->id,
                    $payment->company?->name ?? '-',
                    number_format((float) $payment->amount, 2),
                    str_replace('_', ' ', $payment->method),
                    ucfirst($payment->status),
                    $payment->paid_at?->format('Y-m-d') ?? $payment->created_at->format('Y-m-d'),
                ]),
            ],
            'users' => [
                ['Name', 'Email', 'Role', 'Company', 'Status'],
                $this->applyCommonFilters(User::with('company'), $request, ['name', 'email'])
                    ->when($request->filled('company_id'), fn ($query) => $query->where('company_id', $request->integer('company_id')))
                    ->when($request->filled('role'), fn ($query) => $query->where('role', $request->string('role')->toString()))
                    ->orderBy('name')
                    ->get()
                    ->map(fn ($user) => [
                    $user->name,
                    $user->email,
                    str_replace('_', ' ', $user->role),
                    $user->company?->name ?? 'Platform',
                    ucfirst($user->status),
                ]),
            ],
            'audit-logs' => [
                ['User', 'Company', 'Module', 'Action', 'Description', 'Created'],
                $this->applyCommonFilters(AuditLog::with(['user', 'company']), $request, ['module', 'action', 'description'], false)
                    ->when($request->filled('company_id'), fn ($query) => $query->where('company_id', $request->integer('company_id')))
                    ->latest()
                    ->limit(500)
                    ->get()
                    ->map(fn ($log) => [
                    $log->user?->name ?? 'System',
                    $log->company?->name ?? '-',
                    $log->module ?? '-',
                    str_replace('_', ' ', $log->action),
                    $log->description ?? '-',
                    $log->created_at->format('Y-m-d H:i'),
                ]),
            ],
            'projects' => [
                ['Project', 'Company', 'Status', 'Priority', 'Progress', 'Due'],
                $this->applyCommonFilters(Project::with('company'), $request, ['name', 'priority'])
                    ->when($request->filled('company_id'), fn ($query) => $query->where('company_id', $request->integer('company_id')))
                    ->latest()
                    ->limit(500)
                    ->get()
                    ->map(fn ($project) => [
                    $project->name,
                    $project->company?->name ?? '-',
                    str_replace('_', ' ', $project->status),
                    ucfirst($project->priority),
                    $project->progress.'%',
                    $project->due_date?->format('Y-m-d') ?? '-',
                ]),
            ],
            'tasks' => [
                ['Task', 'Company', 'Project', 'Assignee', 'Status', 'Due'],
                $this->applyCommonFilters(Task::with(['company', 'project', 'assignee']), $request, ['title', 'priority'])
                    ->when($request->filled('company_id'), fn ($query) => $query->where('company_id', $request->integer('company_id')))
                    ->latest()
                    ->limit(500)
                    ->get()
                    ->map(fn ($task) => [
                    $task->title,
                    $task->company?->name ?? '-',
                    $task->project?->name ?? '-',
                    $task->assignee?->name ?? 'Unassigned',
                    str_replace('_', ' ', $task->status),
                    $task->due_date?->format('Y-m-d') ?? '-',
                ]),
            ],
            'subscription-expiry' => [
                ['Company', 'Plan', 'Status', 'Renews', 'Ends', 'Warning'],
                $this->applyCommonFilters(Subscription::with(['company', 'plan'])->where(fn ($query) => $query->whereNotNull('renews_at')->orWhereNotNull('ends_at')), $request)
                    ->when($request->filled('company_id'), fn ($query) => $query->where('company_id', $request->integer('company_id')))
                    ->when($request->filled('plan_id'), fn ($query) => $query->where('subscription_plan_id', $request->integer('plan_id')))
                    ->latest()
                    ->get()
                    ->map(function ($subscription) {
                    $date = $subscription->ends_at ?? $subscription->renews_at;
                    $days = $date ? now()->startOfDay()->diffInDays($date->startOfDay(), false) : null;

                    return [
                        $subscription->company?->name ?? '-',
                        $subscription->plan?->name ?? '-',
                        ucfirst($subscription->status),
                        $subscription->renews_at?->format('Y-m-d') ?? '-',
                        $subscription->ends_at?->format('Y-m-d') ?? '-',
                        $days === null ? '-' : ($days < 0 ? 'Expired' : 'Expires in '.$days.' days'),
                    ];
                }),
            ],
        };

        return [
            'report' => $report,
            'title' => $this->reportTitles()[$report],
            'generatedAt' => now(),
            'headers' => $headers,
            'rows' => $rows,
            'filters' => $this->activeFilters($request),
            'companies' => Company::orderBy('name')->get(),
            'plans' => SubscriptionPlan::orderBy('name')->get(),
        ];
    }

    private function applyCommonFilters($query, Request $request, array $searchColumns = [], bool $filterStatus = true)
    {
        $query
            ->when($filterStatus && $request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('created_at', '>=', Carbon::parse($request->date_from)->toDateString()))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('created_at', '<=', Carbon::parse($request->date_to)->toDateString()));

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
        return collect($request->only(['search', 'date_from', 'date_to', 'status', 'company_id', 'plan_id', 'role']))
            ->filter(fn ($value) => filled($value))
            ->mapWithKeys(fn ($value, $key) => [str_replace('_', ' ', ucfirst($key)) => (string) $value])
            ->all();
    }

    private function reportTitles(): array
    {
        return [
            'companies' => 'Company Registration Report',
            'subscriptions' => 'Subscription Report',
            'payments' => 'Revenue Report',
            'users' => 'Company User Report',
            'audit-logs' => 'Audit Activity Report',
            'projects' => 'Project Monitoring Report',
            'tasks' => 'Task Monitoring Report',
            'subscription-expiry' => 'Subscription Expiry Report',
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
