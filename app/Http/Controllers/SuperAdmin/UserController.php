<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::with('company')
            ->when($request->filled('search'), fn ($query) => $query->where(function ($inner) use ($request): void {
                $inner->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('email', 'like', '%'.$request->search.'%')
                    ->orWhere('username', 'like', '%'.$request->search.'%');
            }))
            ->when($request->filled('role'), fn ($query) => $query->where('role', $request->role))
            ->when($request->filled('company'), fn ($query) => $query->where('company_id', $request->company))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('super-admin.users.index', [
            'users' => $users,
            'companies' => Company::orderBy('name')->get(),
            'roles' => ['super_admin', 'company_admin', 'employee'],
            'statuses' => ['pending', 'active', 'suspended', 'inactive'],
        ]);
    }

    public function show(User $user): View
    {
        return view('super-admin.users.show', [
            'user' => $user->load(['company', 'projects', 'assignedTasks', 'auditLogs']),
        ]);
    }

    public function updateStatus(User $user, string $status): RedirectResponse
    {
        abort_if($user->is(auth()->user()), 403, 'You cannot suspend your own account.');
        abort_unless(in_array($status, ['active', 'suspended', 'inactive'], true), 404);

        $old = ['status' => $user->status];
        $user->update(['status' => $status]);
        AuditLog::create([
            'company_id' => $user->company_id,
            'user_id' => auth()->id(),
            'action' => 'user_'.$status,
            'module' => 'Platform Users',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'description' => 'Updated user status for '.$user->name,
            'old_values' => $old,
            'new_values' => ['status' => $status],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return back()->with('success', 'User status updated.');
    }

    public function sendPasswordReset(User $user): RedirectResponse
    {
        Password::sendResetLink(['email' => $user->email]);

        return back()->with('success', 'Password reset email sent through the secure reset workflow.');
    }
}
