<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Employee\Concerns\HandlesEmployeeAccess;
use App\Services\ReportAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PerformanceController extends Controller
{
    use HandlesEmployeeAccess;

    public function __invoke(Request $request, ReportAnalyticsService $reports): View
    {
        return view('employee.performance.index', $reports->employeePersonalPayload($this->companyId(), auth()->id(), $request));
    }
}
