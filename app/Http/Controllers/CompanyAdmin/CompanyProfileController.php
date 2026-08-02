<?php

namespace App\Http\Controllers\CompanyAdmin;

use App\Http\Controllers\CompanyAdmin\Concerns\HandlesCompanyAccess;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CompanyProfileController extends Controller
{
    use HandlesCompanyAccess;

    public function show(): View
    {
        return view('company-admin.company-profile.show', [
            'company' => $this->company()->load('activeSubscription.plan'),
        ]);
    }

    public function edit(): View
    {
        return view('company-admin.company-profile.edit', ['company' => $this->company()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $company = $this->company();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:companies,email,'.$company->id],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'website' => ['nullable', 'url', 'max:255'],
            'business_type' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:3000'],
            'timezone' => ['required', 'string', 'max:80'],
            'date_format' => ['required', 'string', 'max:30'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        if ($request->hasFile('logo')) {
            if ($company->logo_path) {
                Storage::disk('public')->delete($company->logo_path);
            }

            $data['logo_path'] = $request->file('logo')->store('company-logos', 'public');
        }

        unset($data['logo']);
        $company->update($data);

        return redirect()->route('company-admin.company-profile.show')->with('success', 'Company profile updated successfully.');
    }
}
