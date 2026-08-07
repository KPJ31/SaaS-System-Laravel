<?php

namespace App\Http\Controllers\CompanyAdmin;

use App\Http\Controllers\CompanyAdmin\Concerns\HandlesCompanyAccess;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\Task;
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
            ->withCount([
                'assignedTasks as open_tasks_count' => fn ($query) => $query
                    ->where('company_id', $this->companyId())
                    ->whereIn('status', ['todo', 'assigned', 'in_progress', 'paused', 'blocked', 'submitted', 'under_review']),
                'assignedTasks as overdue_tasks_count' => fn ($query) => $query
                    ->where('company_id', $this->companyId())
                    ->whereDate('due_date', '<', today())
                    ->whereNotIn('status', ['completed', 'cancelled']),
            ])
            ->withSum('workSessions as work_minutes', 'duration_minutes')
            ->where('company_id', $this->companyId())
            ->where('role', 'employee')
            ->when($request->search, fn ($query, $search) => $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('employee_code', 'like', "%{$search}%")))
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->when($request->job_title, fn ($query, $jobTitle) => $query->where('job_title', 'like', "%{$jobTitle}%"))
            ->when($request->department, fn ($query, $department) => $query->where('department', 'like', "%{$department}%"))
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
            'employee_code' => ['nullable', 'string', 'max:100'],
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

        $employee = User::create($data);
        $this->auditEmployee($employee, 'employee_created', 'Employee account created.');

        return redirect()->route('company-admin.employees.index')->with('success', 'Employee created successfully. Temporary password: '.$plainPassword);
    }

    public function show(User $employee): View
    {
        $this->authorizeEmployee($employee);

        return view('company-admin.employees.show', [
            'employee' => $employee->loadCount([
                'projects',
                'permissions',
                'assignedTasks',
                'assignedTasks as open_tasks_count' => fn ($query) => $query->whereIn('status', ['todo', 'assigned', 'in_progress', 'paused', 'blocked', 'submitted', 'under_review']),
                'assignedTasks as overdue_tasks_count' => fn ($query) => $query->whereDate('due_date', '<', today())->whereNotIn('status', ['completed', 'cancelled']),
            ]),
            'projects' => $employee->projects()
                ->where('projects.company_id', $this->companyId())
                ->with('client:id,name')
                ->withCount('tasks')
                ->latest('projects.created_at')
                ->take(6)
                ->get(),
            'tasks' => Task::with('project:id,name')
                ->where('company_id', $this->companyId())
                ->where('assignee_id', $employee->id)
                ->latest()
                ->take(8)
                ->get(),
            'recentAttendances' => Attendance::where('company_id', $this->companyId())
                ->where('user_id', $employee->id)
                ->latest('attendance_date')
                ->take(5)
                ->get(),
            'recentWorkSessions' => $employee->workSessions()
                ->where('company_id', $this->companyId())
                ->with(['project:id,name', 'task:id,title'])
                ->latest('started_at')
                ->take(5)
                ->get(),
            'recentLeaveRequests' => LeaveRequest::where('company_id', $this->companyId())
                ->where('user_id', $employee->id)
                ->latest()
                ->take(5)
                ->get(),
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
            'employee_code' => ['nullable', 'string', 'max:100'],
            'job_title' => ['nullable', 'string', 'max:120'],
            'department' => ['nullable', 'string', 'max:120'],
            'join_date' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'in:pending,active,suspended,inactive'],
        ]);

        $employee->update($data);
        $this->auditEmployee($employee, 'employee_updated', 'Employee profile updated.');

        return redirect()->route('company-admin.employees.show', $employee)->with('success', 'Employee updated successfully.');
    }

    public function destroy(User $employee): RedirectResponse
    {
        $this->authorizeEmployee($employee);
        $employee->update(['status' => 'inactive']);
        $this->auditEmployee($employee, 'employee_deactivated', 'Employee marked inactive.');

        return redirect()->route('company-admin.employees.index')->with('success', 'Employee marked inactive.');
    }

    public function updateStatus(User $employee, string $status): RedirectResponse
    {
        $this->authorizeEmployee($employee);
        abort_unless(in_array($status, ['pending', 'active', 'suspended', 'inactive'], true), 404);

        $employee->update(['status' => $status]);
        $this->auditEmployee($employee, 'employee_status_updated', 'Employee status updated to '.$status.'.');

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

    private function auditEmployee(User $employee, string $action, string $description): void
    {
        \App\Models\AuditLog::create([
            'company_id' => $this->companyId(),
            'user_id' => auth()->id(),
            'action' => $action,
            'module' => 'employees',
            'auditable_type' => User::class,
            'auditable_id' => $employee->id,
            'description' => $description.' '.$employee->name,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
