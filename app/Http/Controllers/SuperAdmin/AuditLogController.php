<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = AuditLog::with(['user', 'company'])
            ->when($request->filled('search'), fn ($query) => $query->where(function ($inner) use ($request): void {
                $inner->where('action', 'like', '%'.$request->search.'%')
                    ->orWhere('description', 'like', '%'.$request->search.'%')
                    ->orWhere('module', 'like', '%'.$request->search.'%');
            }))
            ->when($request->filled('user'), fn ($query) => $query->where('user_id', $request->user))
            ->when($request->filled('company'), fn ($query) => $query->where('company_id', $request->company))
            ->when($request->filled('module'), fn ($query) => $query->where('module', $request->module))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('created_at', '>=', $request->from))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('created_at', '<=', $request->to))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('super-admin.audit-logs.index', [
            'logs' => $logs,
            'users' => User::orderBy('name')->get(),
            'companies' => Company::orderBy('name')->get(),
            'modules' => AuditLog::whereNotNull('module')->distinct()->orderBy('module')->pluck('module'),
        ]);
    }

    public function show(AuditLog $auditLog): View
    {
        return view('super-admin.audit-logs.show', [
            'log' => $auditLog->load(['user', 'company']),
        ]);
    }
}
