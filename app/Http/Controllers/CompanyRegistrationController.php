<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompanyRegistrationRequest;
use App\Models\CompanyRegistrationRequest;
use App\Notifications\CompanyRegistrationReceived;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class CompanyRegistrationController extends Controller
{
    public function create(): View
    {
        return view('auth.register-company');
    }

    public function store(StoreCompanyRegistrationRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('company-logos', 'public');
        }

        $data['status'] = 'pending';
        CompanyRegistrationRequest::create($data);

        $superAdmins = User::where('role', 'super_admin')->where('status', 'active')->get();
        Notification::send($superAdmins, new CompanyRegistrationReceived($data['company_name']));

        return redirect()
            ->route('company.register.submitted')
            ->with('company_registration_email', $data['admin_email'])
            ->with('success', 'Your company registration request was submitted for review.');
    }

    public function submitted(): View
    {
        return view('auth.registration-submitted');
    }
}
