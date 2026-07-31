<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\CompanyRegistrationRequest;
use App\Models\Project;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('super-admin.dashboard', [
            'companiesCount' => Company::count(),
            'pendingRequestsCount' => CompanyRegistrationRequest::where('status', 'pending')->count(),
            'employeesCount' => User::where('role', 'employee')->count(),
            'projectsCount' => Project::count(),
            'latestRequests' => CompanyRegistrationRequest::latest()->take(5)->get(),
            'latestAuditLogs' => AuditLog::with('user')->latest()->take(6)->get(),
        ]);
    }
}
