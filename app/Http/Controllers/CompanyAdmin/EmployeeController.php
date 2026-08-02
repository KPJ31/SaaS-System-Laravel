<?php

namespace App\Http\Controllers\CompanyAdmin;

use App\Http\Controllers\CompanyAdmin\Concerns\HandlesCompanyAccess;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    use HandlesCompanyAccess;

    public function index(Request $request): View
    {
        $employees = User::withCount(['projects', 'assignedTasks'])
            ->withSum('workSessions as work_minutes', 'duration_minutes')
            ->where('company_id', $this->companyId())
            ->where('role', 'employee')
            ->when($request->search, fn ($query, $search) => $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->when($request->job_title, fn ($query, $jobTitle) => $query->where('job_title', 'like', "%{$jobTitle}%"))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('company-admin.employees.index', compact('employees'));
    }

    public function create(): View|RedirectResponse
    {
        if ($this->subscriptionLimitReached('employee_limit', User::class, ['role' => 'employee'])) {
            return redirect()->route('company-admin.employees.index')->with('error', $this->limitMessage());
        }

        return view('company-admin.employees.form', ['employee' => new User()]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($this->subscriptionLimitReached('employee_limit', User::class, ['role' => 'employee'])) {
            return back()->withInput()->with('error', $this->limitMessage());
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:100', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'job_title' => ['nullable', 'string', 'max:120'],
            'department' => ['nullable', 'string', 'max:120'],
            'join_date' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'in:pending,active,suspended,inactive'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $plainPassword = $data['password'] ?? Str::password(12);
        $data['company_id'] = $this->companyId();
        $data['role'] = 'employee';
        $data['password'] = Hash::make($plainPassword);
        $data['must_change_password'] = true;

        User::create($data);

        return redirect()->route('company-admin.employees.index')->with('success', 'Employee created successfully. Temporary password: '.$plainPassword);
    }

    public function show(User $employee): View
    {
        $this->authorizeEmployee($employee);

        return view('company-admin.employees.show', [
            'employee' => $employee->load(['projects', 'assignedTasks.project', 'workSessions.project']),
        ]);
    }

    public function edit(User $employee): View
    {
        $this->authorizeEmployee($employee);

        return view('company-admin.employees.form', compact('employee'));
    }

    public function update(Request $request, User $employee): RedirectResponse
    {
        $this->authorizeEmployee($employee);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:100', 'unique:users,username,'.$employee->id],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$employee->id],
            'phone' => ['nullable', 'string', 'max:50'],
            'job_title' => ['nullable', 'string', 'max:120'],
            'department' => ['nullable', 'string', 'max:120'],
            'join_date' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'in:pending,active,suspended,inactive'],
        ]);

        $employee->update($data);

        return redirect()->route('company-admin.employees.show', $employee)->with('success', 'Employee updated successfully.');
    }

    public function destroy(User $employee): RedirectResponse
    {
        $this->authorizeEmployee($employee);
        $employee->update(['status' => 'inactive']);

        return redirect()->route('company-admin.employees.index')->with('success', 'Employee marked inactive.');
    }

    public function updateStatus(User $employee, string $status): RedirectResponse
    {
        $this->authorizeEmployee($employee);
        abort_unless(in_array($status, ['pending', 'active', 'suspended', 'inactive'], true), 404);

        $employee->update(['status' => $status]);

        return back()->with('success', 'Employee status updated.');
    }

    public function sendPasswordReset(User $employee): RedirectResponse
    {
        $this->authorizeEmployee($employee);

        try {
            Password::sendResetLink(['email' => $employee->email]);
        } catch (\Throwable $exception) {
            Log::warning('Employee password reset mail failed', ['employee_id' => $employee->id, 'error' => $exception->getMessage()]);
        }

        return back()->with('success', 'Password reset workflow started.');
    }

    private function authorizeEmployee(User $employee): void
    {
        if ((int) $employee->company_id !== $this->companyId() || $employee->role !== 'employee') {
            abort(403);
        }
    }
}
