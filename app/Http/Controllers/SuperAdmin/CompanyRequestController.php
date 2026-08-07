<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RejectCompanyRequest;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\CompanyRegistrationRequest;
use App\Models\CompanySetting;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Notifications\CompanyRegistrationApproved;
use App\Notifications\CompanyRegistrationRejected;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CompanyRequestController extends Controller
{
    public function index(Request $request): View
    {
        $requests = CompanyRegistrationRequest::query()
            ->when($request->filled('search'), fn ($query) => $query->where(function ($inner) use ($request): void {
                $inner->where('company_name', 'like', '%'.$request->search.'%')
                    ->orWhere('company_email', 'like', '%'.$request->search.'%')
                    ->orWhere('admin_name', 'like', '%'.$request->search.'%')
                    ->orWhere('admin_email', 'like', '%'.$request->search.'%');
            }))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('created_at', '>=', $request->from))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('created_at', '<=', $request->to))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('super-admin.company-requests.index', [
            'requests' => $requests,
            'statuses' => ['pending', 'approved', 'rejected'],
            'summary' => [
                'pending' => CompanyRegistrationRequest::where('status', 'pending')->count(),
                'approved' => CompanyRegistrationRequest::where('status', 'approved')->count(),
                'rejected' => CompanyRegistrationRequest::where('status', 'rejected')->count(),
            ],
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

        $mailWarning = false;
        [$admin, $companyName] = DB::transaction(function () use ($companyRequest): array {
            $company = Company::create([
                'name' => $companyRequest->company_name,
                'slug' => Str::slug($companyRequest->company_name).'-'.Str::lower(Str::random(5)),
                'email' => $companyRequest->company_email,
                'phone' => $companyRequest->company_phone,
                'address' => $companyRequest->company_address,
                'website' => $companyRequest->website,
                'logo_path' => $companyRequest->logo_path,
                'status' => 'active',
            ]);

            CompanySetting::create(['company_id' => $company->id]);
            $plan = $this->defaultPlan();

            Subscription::create([
                'company_id' => $company->id,
                'subscription_plan_id' => $plan->id,
                'status' => $plan->trial_days > 0 ? 'trialing' : 'active',
                'starts_at' => now()->toDateString(),
                'trial_ends_at' => $plan->trial_days > 0 ? now()->addDays($plan->trial_days)->toDateString() : null,
                'renews_at' => now()->addMonth()->toDateString(),
                'monthly_price' => $plan->monthly_price,
            ]);

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
                'metadata' => ['subscription_plan' => $plan->name],
            ]);

            return [$admin, $company->name];
        });

        try {
            $admin->notify(new CompanyRegistrationApproved($companyName, $admin->username));
        } catch (\Throwable $exception) {
            $mailWarning = true;
            Log::warning('Company approval email failed.', [
                'company_registration_request_id' => $companyRequest->id,
                'admin_email' => $admin->email,
                'error' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('super-admin.company-requests.index')
            ->with('success', $mailWarning
                ? 'Company request approved successfully, but the approval email could not be sent.'
                : 'Company request approved successfully.');
    }

    private function defaultPlan(): SubscriptionPlan
    {
        return SubscriptionPlan::where('status', 'active')->orderBy('display_order')->first()
            ?? SubscriptionPlan::create([
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Default starter plan for newly approved companies.',
                'monthly_price' => 49,
                'annual_price' => 499,
                'employee_limit' => 10,
                'client_limit' => 25,
                'project_limit' => 20,
                'storage_limit_mb' => 2048,
                'trial_days' => 14,
                'features' => ['Company dashboard', 'Projects and tasks', 'Invoices and payments'],
                'status' => 'active',
                'display_order' => 1,
            ]);
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
        });

        $mailWarning = false;

        try {
            Notification::route('mail', $companyRequest->admin_email)
                ->notify(new CompanyRegistrationRejected($companyRequest->company_name, $companyRequest->rejection_reason));
        } catch (\Throwable $exception) {
            $mailWarning = true;
            Log::warning('Company rejection email failed.', [
                'company_registration_request_id' => $companyRequest->id,
                'admin_email' => $companyRequest->admin_email,
                'error' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('super-admin.company-requests.index')
            ->with('success', $mailWarning
                ? 'Company request rejected, but the rejection email could not be sent.'
                : 'Company request rejected.');
    }
}
