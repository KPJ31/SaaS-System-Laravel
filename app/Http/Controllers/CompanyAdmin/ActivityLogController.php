<?php

namespace App\Http\Controllers\CompanyAdmin;

use App\Http\Controllers\CompanyAdmin\Concerns\HandlesCompanyAccess;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    use HandlesCompanyAccess;

    public function index(Request $request): View
    {
        $logs = AuditLog::with('user')
            ->where('company_id', $this->companyId())
            ->when($request->action, fn ($query, $action) => $query->where('action', 'like', "%{$action}%"))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('company-admin.activity-logs.index', compact('logs'));
    }

    public function show(AuditLog $auditLog): View
    {
        $this->abortUnlessCompanyRecord($auditLog);

        return view('company-admin.activity-logs.show', compact('auditLog'));
    }
}
