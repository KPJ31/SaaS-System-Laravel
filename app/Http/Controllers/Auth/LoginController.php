<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = $request->user();

        if ($user->status !== 'active') {
            Auth::logout();

            return back()->withErrors(['login' => 'Your user account is not active.']);
        }

        if ($user->role !== 'super_admin' && (! $user->company || $user->company->status !== 'active')) {
            Auth::logout();

            return back()->withErrors(['login' => 'Your company account is not approved for access.']);
        }

        $request->session()->regenerate();

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        return redirect()->intended(match ($user->role) {
            'super_admin' => route('super-admin.dashboard'),
            'company_admin' => route('company-admin.dashboard'),
            default => route('employee.dashboard'),
        });
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'You have been signed out.');
    }
}
