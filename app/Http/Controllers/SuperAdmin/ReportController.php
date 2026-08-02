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

    public function show(string $report): View
    {
        return view('super-admin.reports.show', $this->reportPayload($report));
    }

    public function export(string $report): StreamedResponse
    {
        $payload = $this->reportPayload($report);

        $filename = 'elevanix-'.$report.'-'.now()->format('Y-m-d').'.csv';

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
        $filename = 'elevanix-'.$report.'-'.now()->format('Y-m-d').'.pdf';

        return Pdf::loadView('super-admin.reports.pdf', $payload)
            ->setPaper('a4', 'landscape')
            ->download($filename);
    }

    private function reportPayload(string $report): array
    {
        abort_unless(array_key_exists($report, $this->reportTitles()), 404);

        [$headers, $rows] = match ($report) {
            'companies' => [
                ['Name', 'Email', 'Status', 'Created'],
                Company::orderBy('name')->get()->map(fn ($company) => [
                    $company->name,
                    $company->email,
                    ucfirst($company->status),
                    $company->created_at->format('Y-m-d'),
                ]),
            ],
            'subscriptions' => [
                ['Company', 'Plan', 'Status', 'Starts', 'Renews', 'Ends'],
                Subscription::with(['company', 'plan'])->latest()->get()->map(fn ($subscription) => [
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
                Payment::with('company')->where('payment_type', 'subscription')->latest()->get()->map(fn ($payment) => [
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
                User::with('company')->orderBy('name')->get()->map(fn ($user) => [
                    $user->name,
                    $user->email,
                    str_replace('_', ' ', $user->role),
                    $user->company?->name ?? 'Platform',
                    ucfirst($user->status),
                ]),
            ],
            'audit-logs' => [
                ['User', 'Company', 'Module', 'Action', 'Description', 'Created'],
                AuditLog::with(['user', 'company'])->latest()->limit(500)->get()->map(fn ($log) => [
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
                Project::with('company')->latest()->limit(500)->get()->map(fn ($project) => [
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
                Task::with(['company', 'project', 'assignee'])->latest()->limit(500)->get()->map(fn ($task) => [
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
                Subscription::with(['company', 'plan'])->where(fn ($query) => $query->whereNotNull('renews_at')->orWhereNotNull('ends_at'))->latest()->get()->map(function ($subscription) {
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
        ];
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
}
