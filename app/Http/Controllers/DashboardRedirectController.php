<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class DashboardRedirectController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        return redirect()->route(match (auth()->user()->role) {
            'super_admin' => 'super-admin.dashboard',
            'company_admin' => 'company-admin.dashboard',
            default => 'employee.dashboard',
        });
    }
}
