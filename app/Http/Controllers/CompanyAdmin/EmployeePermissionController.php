<?php

namespace App\Http\Controllers\CompanyAdmin;

use App\Http\Controllers\CompanyAdmin\Concerns\HandlesCompanyAccess;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeePermissionController extends Controller
{
    use HandlesCompanyAccess;

    public function index(Request $request): View
    {
        $employees = User::with('permissions')
            ->where('company_id', $this->companyId())
            ->where('role', 'employee')
            ->when($request->employee, fn ($query, $employee) => $query->where('name', 'like', "%{$employee}%"))
            ->when($request->department, fn ($query, $department) => $query->where('department', 'like', "%{$department}%"))
            ->when($request->permission, fn ($query, $permission) => $query->whereHas('permissions', fn ($q) => $q->where('name', $permission)))
            ->when($request->filled('extra_permissions'), function ($query) use ($request): void {
                if ($request->extra_permissions === 'yes') {
                    $query->has('permissions');
                }

                if ($request->extra_permissions === 'no') {
                    $query->doesntHave('permissions');
                }
            })
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        $latestUpdates = AuditLog::with('user')
            ->where('company_id', $this->companyId())
            ->where('module', 'employee-permissions')
            ->latest()
            ->take(20)
            ->get()
            ->keyBy('auditable_id');

        return view('company-admin.employees.permissions-index', [
            'employees' => $employees,
            'permissionLabels' => PermissionCatalog::labels(),
            'latestUpdates' => $latestUpdates,
        ]);
    }

    public function edit(User $employee): View
    {
        $this->authorizeEmployee($employee);

        $employee->load('permissions');
        $companyEmployees = User::where('company_id', $this->companyId())
            ->where('role', 'employee')
            ->whereKeyNot($employee->id)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $latestUpdate = AuditLog::with('user')
            ->where('company_id', $this->companyId())
            ->where('module', 'employee-permissions')
            ->where('auditable_type', User::class)
            ->where('auditable_id', $employee->id)
            ->latest()
            ->first();

        return view('company-admin.employees.permissions', [
            'employee' => $employee,
            'groups' => PermissionCatalog::groups(),
            'templates' => PermissionCatalog::templates(),
            'assignedPermissions' => $employee->permissions->pluck('name')->all(),
            'companyEmployees' => $companyEmployees,
            'latestUpdate' => $latestUpdate,
        ]);
    }

    public function update(Request $request, User $employee): RedirectResponse
    {
        $this->authorizeEmployee($employee);

        $permissions = $this->validatedPermissions($request);
        $before = $employee->permissions()->pluck('name')->all();

        $employee->syncDirectPermissions($permissions);
        $this->auditPermissions($employee, $before, $permissions, 'updated employee permissions');

        return redirect()
            ->route('company-admin.employees.permissions.edit', $employee)
            ->with('success', 'Employee permissions updated successfully.');
    }

    public function reset(User $employee): RedirectResponse
    {
        $this->authorizeEmployee($employee);

        $before = $employee->permissions()->pluck('name')->all();
        $employee->syncDirectPermissions([]);
        $this->auditPermissions($employee, $before, [], 'reset employee permissions');

        return redirect()
            ->route('company-admin.employees.permissions.edit', $employee)
            ->with('success', 'Employee permissions reset successfully.');
    }

    public function copy(Request $request, User $employee): RedirectResponse
    {
        $this->authorizeEmployee($employee);

        $data = $request->validate([
            'source_employee_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')
                    ->where(fn ($query) => $query
                        ->where('company_id', $this->companyId())
                        ->where('role', 'employee')),
            ],
        ]);

        $sourceEmployee = User::with('permissions')->findOrFail($data['source_employee_id']);
        $this->authorizeEmployee($sourceEmployee);

        $before = $employee->permissions()->pluck('name')->all();
        $permissions = $sourceEmployee->permissions
            ->pluck('name')
            ->intersect(PermissionCatalog::assignableNames())
            ->values()
            ->all();

        $employee->syncDirectPermissions($permissions);
        $this->auditPermissions($employee, $before, $permissions, 'copied employee permissions', [
            'source_employee_id' => $sourceEmployee->id,
            'source_employee_name' => $sourceEmployee->name,
        ]);

        return redirect()
            ->route('company-admin.employees.permissions.edit', $employee)
            ->with('success', 'Employee permissions copied successfully.');
    }

    private function validatedPermissions(Request $request): array
    {
        $allowed = PermissionCatalog::assignableNames();

        $data = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name'), Rule::in($allowed)],
        ]);

        return collect($data['permissions'] ?? [])
            ->unique()
            ->values()
            ->all();
    }

    private function authorizeEmployee(User $employee): void
    {
        if ((int) $employee->company_id !== $this->companyId() || $employee->role !== 'employee') {
            abort(403);
        }
    }

    private function auditPermissions(User $employee, array $before, array $after, string $action, array $metadata = []): void
    {
        $added = array_values(array_diff($after, $before));
        $removed = array_values(array_diff($before, $after));

        AuditLog::create([
            'company_id' => $this->companyId(),
            'user_id' => auth()->id(),
            'action' => $action,
            'module' => 'employee-permissions',
            'auditable_type' => User::class,
            'auditable_id' => $employee->id,
            'description' => auth()->user()->name.' updated permissions for '.$employee->name.'.',
            'old_values' => ['permissions' => array_values($before)],
            'new_values' => ['permissions' => array_values($after)],
            'metadata' => array_merge(['added' => $added, 'removed' => $removed], $metadata),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
