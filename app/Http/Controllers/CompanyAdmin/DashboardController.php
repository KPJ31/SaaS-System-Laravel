<?php

namespace App\Http\Controllers\CompanyAdmin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $companyId = auth()->user()->company_id;

        return view('company-admin.dashboard', [
            'clientsCount' => Client::where('company_id', $companyId)->count(),
            'employeesCount' => User::where('company_id', $companyId)->where('role', 'employee')->count(),
            'projectsCount' => Project::where('company_id', $companyId)->count(),
            'tasksCount' => Task::where('company_id', $companyId)->count(),
            'projects' => Project::with('client')->where('company_id', $companyId)->latest()->take(6)->get(),
            'tasks' => Task::with(['project', 'assignee'])->where('company_id', $companyId)->latest()->take(8)->get(),
        ]);
    }
}
