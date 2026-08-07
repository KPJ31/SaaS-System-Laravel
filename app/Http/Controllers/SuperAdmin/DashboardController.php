<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\SuperAdminDashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(SuperAdminDashboardService $dashboard): View
    {
        return view('super-admin.dashboard', $dashboard->payload());
    }
}
