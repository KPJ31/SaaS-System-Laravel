<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function index(Request $request): View
    {
        $activities = AuditLog::where('company_id', auth()->user()->company_id)
            ->where('user_id', auth()->id())
            ->when($request->search, fn ($query, $search) => $query->where(fn ($inner) => $inner
                ->where('description', 'like', "%{$search}%")
                ->orWhere('action', 'like', "%{$search}%")))
            ->when($request->module, fn ($query, $module) => $query->where('module', $module))
            ->when($request->date, fn ($query, $date) => $query->whereDate('created_at', $date))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('employee.activity.index', ['activities' => $activities]);
    }
}
