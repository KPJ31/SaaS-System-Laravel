<?php

namespace App\Http\Controllers\CompanyAdmin;

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
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $companyId = auth()->user()->company_id;
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        return view('company-admin.dashboard', [
            'clientsCount' => Client::where('company_id', $companyId)->count(),
            'employeesCount' => User::where('company_id', $companyId)->where('role', 'employee')->count(),
            'activeEmployeesCount' => User::where('company_id', $companyId)->where('role', 'employee')->where('status', 'active')->count(),
            'suspendedEmployeesCount' => User::where('company_id', $companyId)->where('role', 'employee')->where('status', 'suspended')->count(),
            'projectsCount' => Project::where('company_id', $companyId)->count(),
            'activeProjectsCount' => Project::where('company_id', $companyId)->whereIn('status', ['active', 'in_progress', 'testing'])->count(),
            'completedProjectsCount' => Project::where('company_id', $companyId)->where('status', 'completed')->count(),
            'pendingRequestsCount' => ProjectRequest::where('company_id', $companyId)->whereIn('status', ['pending', 'under_review'])->count(),
            'tasksCount' => Task::where('company_id', $companyId)->count(),
            'pendingTasksCount' => Task::where('company_id', $companyId)->whereIn('status', ['todo', 'assigned'])->count(),
            'inProgressTasksCount' => Task::where('company_id', $companyId)->where('status', 'in_progress')->count(),
            'completedTasksCount' => Task::where('company_id', $companyId)->where('status', 'completed')->count(),
            'overdueTasksCount' => Task::where('company_id', $companyId)->whereDate('due_date', '<', today())->whereNotIn('status', ['completed', 'cancelled'])->count(),
            'todayWorkMinutes' => WorkSession::where('company_id', $companyId)->whereDate('started_at', today())->sum('duration_minutes'),
            'monthWorkMinutes' => WorkSession::where('company_id', $companyId)->whereBetween('started_at', [$monthStart, $monthEnd])->sum('duration_minutes'),
            'pendingPaymentsCount' => Payment::where('company_id', $companyId)->where('payment_type', 'client_project')->whereIn('status', ['pending', 'requested', 'proof_submitted'])->count(),
            'paidInvoicesCount' => Invoice::where('company_id', $companyId)->where('status', 'paid')->count(),
            'monthlyRevenue' => Payment::where('company_id', $companyId)->where('payment_type', 'client_project')->whereIn('status', ['paid', 'received', 'verified'])->whereBetween('created_at', [$monthStart, $monthEnd])->sum('amount'),
            'totalRevenue' => Payment::where('company_id', $companyId)->where('payment_type', 'client_project')->whereIn('status', ['paid', 'received', 'verified'])->sum('amount'),
            'projects' => Project::with('client')->where('company_id', $companyId)->latest()->take(6)->get(),
            'tasks' => Task::with(['project', 'assignee'])->where('company_id', $companyId)->latest()->take(8)->get(),
            'projectRequests' => ProjectRequest::with('client')->where('company_id', $companyId)->latest()->take(5)->get(),
            'payments' => Payment::with(['client', 'project'])->where('company_id', $companyId)->where('payment_type', 'client_project')->latest()->take(5)->get(),
            'employees' => User::where('company_id', $companyId)->where('role', 'employee')->latest()->take(5)->get(),
            'feedback' => Feedback::with(['client', 'project'])->where('company_id', $companyId)->latest()->take(5)->get(),
            'chartData' => [
                'projectStatusLabels' => Project::where('company_id', $companyId)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('status')->values(),
                'projectStatusValues' => Project::where('company_id', $companyId)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total')->values(),
                'taskStatusLabels' => Task::where('company_id', $companyId)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('status')->values(),
                'taskStatusValues' => Task::where('company_id', $companyId)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total')->values(),
            ],
        ]);
    }
}
