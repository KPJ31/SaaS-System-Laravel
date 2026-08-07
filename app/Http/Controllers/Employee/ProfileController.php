<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(): View
    {
        return view('employee.profile.show', ['user' => auth()->user()->load('company')]);
    }

    public function update(Request $request, AuditLogger $logger): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.auth()->id()],
            'username' => ['nullable', 'string', 'max:255', 'unique:users,username,'.auth()->id()],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:1000'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_avatar' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('remove_avatar') && auth()->user()->avatar) {
            Storage::disk('public')->delete(auth()->user()->avatar);
            $data['avatar'] = null;
        }

        if ($request->hasFile('avatar')) {
            if (auth()->user()->avatar) {
                Storage::disk('public')->delete(auth()->user()->avatar);
            }
            $data['avatar'] = $request->file('avatar')->store('profile-images', 'public');
        }

        unset($data['remove_avatar']);
        auth()->user()->update($data);
        $logger->record('profile_updated', 'Employee profile updated.', auth()->user(), auth()->user(), auth()->user()->company_id, request: $request);

        return back()->with('success', 'Profile updated.');
    }
}
