<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(Request $request): View
    {
        return view('super-admin.companies.index', [
            'companies' => Company::with(['activeSubscription.plan', 'users'])
                ->withCount(['users', 'projects'])
                ->when($request->filled('search'), fn ($query) => $query->where(function ($inner) use ($request): void {
                    $inner->where('name', 'like', '%'.$request->search.'%')
                        ->orWhere('email', 'like', '%'.$request->search.'%');
                }))
                ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
                ->when($request->filled('from'), fn ($query) => $query->whereDate('created_at', '>=', $request->from))
                ->when($request->filled('to'), fn ($query) => $query->whereDate('created_at', '<=', $request->to))
                ->latest()
                ->paginate(10)
                ->withQueryString(),
            'statuses' => ['pending', 'active', 'suspended', 'rejected'],
        ]);
    }

    public function show(Company $company): View
    {
        return view('super-admin.companies.show', [
            'company' => $company->load(['users', 'activeSubscription.plan', 'subscriptions.plan', 'payments', 'setting']),
            'companyAdmin' => $company->users()->where('role', 'company_admin')->first(),
            'recentActivities' => AuditLog::with('user')->where('company_id', $company->id)->latest()->take(8)->get(),
        ]);
    }

    public function edit(Company $company): View
    {
        return view('super-admin.companies.edit', ['company' => $company]);
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:companies,email,'.$company->id],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'in:pending,active,suspended,rejected'],
        ]);

        $old = $company->only(array_keys($data));
        $company->update($data);
        AuditLog::create([
            'company_id' => $company->id,
            'user_id' => auth()->id(),
            'action' => 'company_updated',
            'module' => 'Companies',
            'auditable_type' => Company::class,
            'auditable_id' => $company->id,
            'description' => 'Updated company information for '.$company->name,
            'old_values' => $old,
            'new_values' => $data,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('super-admin.companies.show', $company)->with('success', 'Company information updated.');
    }

    public function updateStatus(Company $company, string $status): RedirectResponse
    {
        abort_unless(in_array($status, ['pending', 'active', 'suspended', 'rejected'], true), 404);

        $old = ['status' => $company->status];
        $company->update(['status' => $status]);
        AuditLog::create([
            'company_id' => $company->id,
            'user_id' => auth()->id(),
            'action' => 'company_'.$status,
            'module' => 'Companies',
            'auditable_type' => Company::class,
            'auditable_id' => $company->id,
            'description' => ucfirst($status).' company '.$company->name,
            'old_values' => $old,
            'new_values' => ['status' => $status],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return back()->with('success', 'Company status updated.');
    }
}
