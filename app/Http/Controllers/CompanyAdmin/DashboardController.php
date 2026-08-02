<?php

namespace App\Http\Controllers\CompanyAdmin;

use App\Http\Controllers\Controller;
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
use Illuminate\Support\Facades\DB;
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
            'employeesWithPermissionsCount' => User::where('company_id', $companyId)->where('role', 'employee')->has('permissions')->count(),
            'employeesWithoutPermissionsCount' => User::where('company_id', $companyId)->where('role', 'employee')->doesntHave('permissions')->count(),
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
            'weekWorkMinutes' => WorkSession::where('company_id', $companyId)->whereBetween('started_at', [now()->startOfWeek(), now()->endOfWeek()])->sum('duration_minutes'),
            'pendingLeavesCount' => LeaveRequest::where('company_id', $companyId)->where('status', 'pending')->count(),
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
            'leaveRequests' => LeaveRequest::with('user')->where('company_id', $companyId)->latest()->take(5)->get(),
            'latestActivities' => \App\Models\AuditLog::with('user')->where('company_id', $companyId)->latest()->take(6)->get(),
            'recentPermissionUpdates' => \App\Models\AuditLog::with('user')
                ->where('company_id', $companyId)
                ->where('module', 'employee-permissions')
                ->latest()
                ->take(5)
                ->get(),
            'topPermissionModules' => DB::table('permission_user')
                ->join('permissions', 'permissions.id', '=', 'permission_user.permission_id')
                ->join('users', 'users.id', '=', 'permission_user.user_id')
                ->where('users.company_id', $companyId)
                ->where('users.role', 'employee')
                ->select('permissions.module', DB::raw('count(*) as total'))
                ->groupBy('permissions.module')
                ->orderByDesc('total')
                ->take(5)
                ->get(),
            'chartData' => [
                'projectStatusLabels' => Project::where('company_id', $companyId)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('status')->values(),
                'projectStatusValues' => Project::where('company_id', $companyId)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total')->values(),
                'taskStatusLabels' => Task::where('company_id', $companyId)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('status')->values(),
                'taskStatusValues' => Task::where('company_id', $companyId)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total')->values(),
                'employeeHoursLabels' => User::where('company_id', $companyId)->where('role', 'employee')->orderBy('name')->limit(8)->pluck('name'),
                'employeeHoursValues' => User::where('company_id', $companyId)->where('role', 'employee')->orderBy('name')->limit(8)->get()->map(fn ($employee) => round($employee->workSessions()->whereBetween('started_at', [$monthStart, $monthEnd])->sum('duration_minutes') / 60, 2)),
                'paymentStatusLabels' => Payment::where('company_id', $companyId)->where('payment_type', 'client_project')->selectRaw('status, count(*) as total')->groupBy('status')->pluck('status')->values(),
                'paymentStatusValues' => Payment::where('company_id', $companyId)->where('payment_type', 'client_project')->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total')->values(),
            ],
        ]);
    }
}
