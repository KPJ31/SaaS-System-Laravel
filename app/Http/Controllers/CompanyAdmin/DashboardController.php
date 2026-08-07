<?php

namespace App\Http\Controllers\CompanyAdmin;

use App\Http\Controllers\Controller;
use App\Services\CompanyAdminDashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(CompanyAdminDashboardService $dashboard): View
    {
        return view('company-admin.dashboard', $dashboard->dataFor(auth()->user()));
    }
}
