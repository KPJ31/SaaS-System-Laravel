<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RejectCompanyRequest;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\CompanyRegistrationRequest;
use App\Models\CompanySetting;
use App\Models\User;
use App\Notifications\CompanyRegistrationApproved;
use App\Notifications\CompanyRegistrationRejected;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class CompanyRequestController extends Controller
{
    public function index(): View
    {
        return view('super-admin.company-requests.index', [
            'requests' => CompanyRegistrationRequest::latest()->paginate(10),
        ]);
    }

    public function show(CompanyRegistrationRequest $companyRequest): View
    {
        return view('super-admin.company-requests.show', ['request' => $companyRequest]);
    }

    public function approve(CompanyRegistrationRequest $companyRequest): RedirectResponse
    {
        if ($companyRequest->status !== 'pending') {
            return back()->with('error', 'Only pending requests can be approved.');
        }

        DB::transaction(function () use ($companyRequest): void {
            $company = Company::create([
                'name' => $companyRequest->company_name,
                'email' => $companyRequest->company_email,
                'phone' => $companyRequest->company_phone,
                'address' => $companyRequest->company_address,
                'website' => $companyRequest->website,
                'logo_path' => $companyRequest->logo_path,
                'status' => 'active',
            ]);

            CompanySetting::create(['company_id' => $company->id]);

            $admin = User::create([
                'company_id' => $company->id,
                'name' => $companyRequest->admin_name,
                'username' => $companyRequest->username,
                'email' => $companyRequest->admin_email,
                'role' => 'company_admin',
                'status' => 'active',
                'password' => $companyRequest->password,
            ]);

            $companyRequest->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            AuditLog::create([
                'company_id' => $company->id,
                'user_id' => auth()->id(),
                'action' => 'company_request_approved',
                'auditable_type' => CompanyRegistrationRequest::class,
                'auditable_id' => $companyRequest->id,
                'description' => 'Approved company registration for '.$company->name,
            ]);

            $admin->notify(new CompanyRegistrationApproved($company->name, $admin->username));
        });

        return redirect()->route('super-admin.company-requests.index')->with('success', 'Company request approved successfully.');
    }

    public function reject(RejectCompanyRequest $request, CompanyRegistrationRequest $companyRequest): RedirectResponse
    {
        if ($companyRequest->status !== 'pending') {
            return back()->with('error', 'Only pending requests can be rejected.');
        }

        DB::transaction(function () use ($request, $companyRequest): void {
            $companyRequest->update([
                'status' => 'rejected',
                'rejected_by' => auth()->id(),
                'rejected_at' => now(),
                'rejection_reason' => $request->validated()['rejection_reason'],
            ]);

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'company_request_rejected',
                'auditable_type' => CompanyRegistrationRequest::class,
                'auditable_id' => $companyRequest->id,
                'description' => 'Rejected company registration for '.$companyRequest->company_name,
            ]);

            Notification::route('mail', $companyRequest->admin_email)
                ->notify(new CompanyRegistrationRejected($companyRequest->company_name, $companyRequest->rejection_reason));
        });

        return redirect()->route('super-admin.company-requests.index')->with('success', 'Company request rejected.');
    }
}
