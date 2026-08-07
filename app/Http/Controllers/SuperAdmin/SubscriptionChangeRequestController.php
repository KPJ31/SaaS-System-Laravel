<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Project;
use App\Models\SubscriptionChangeRequest;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Notifications\SubscriptionChangeStatusNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class SubscriptionChangeRequestController extends Controller
{
    public function index(Request $request): View
    {
        $requests = SubscriptionChangeRequest::with(['company', 'currentPlan', 'requestedPlan', 'payment'])
            ->when($request->filled('company_id'), fn ($query) => $query->where('company_id', $request->integer('company_id')))
            ->when($request->filled('current_plan_id'), fn ($query) => $query->where('current_plan_id', $request->integer('current_plan_id')))
            ->when($request->filled('requested_plan_id'), fn ($query) => $query->where('requested_plan_id', $request->integer('requested_plan_id')))
            ->when($request->filled('change_type'), fn ($query) => $query->where('change_type', $request->change_type))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('payment_status'), fn ($query) => $query->whereHas('payment', fn ($payment) => $payment->where('status', $request->payment_status)))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('super-admin.subscription-change-requests.index', [
            'requests' => $requests,
            'companies' => Company::orderBy('name')->get(),
            'plans' => SubscriptionPlan::orderBy('name')->get(),
            'statuses' => ['pending', 'payment_required', 'payment_submitted', 'under_review', 'approved', 'rejected', 'completed', 'cancelled'],
            'changeTypes' => ['upgrade', 'downgrade', 'lateral_change'],
            'paymentStatuses' => ['pending', 'submitted', 'verified', 'rejected', 'failed', 'refunded', 'received', 'paid'],
        ]);
    }

    public function show(SubscriptionChangeRequest $subscriptionChangeRequest): View
    {
        return view('super-admin.subscription-change-requests.show', [
            'changeRequest' => $subscriptionChangeRequest->load(['company.users', 'currentSubscription', 'currentPlan', 'requestedPlan', 'payment', 'requester', 'reviewer']),
            'usage' => $this->usage((int) $subscriptionChangeRequest->company_id),
        ]);
    }

    public function approve(Request $request, SubscriptionChangeRequest $subscriptionChangeRequest): RedirectResponse
    {
        if (! in_array($subscriptionChangeRequest->status, ['pending', 'payment_submitted', 'under_review', 'approved'], true)) {
            return back()->with('error', 'This request is not ready for approval.');
        }

        $data = $request->validate(['review_note' => ['nullable', 'string', 'max:2000']]);
        $subscriptionChangeRequest->load(['requestedPlan', 'payment', 'currentSubscription', 'company.users']);

        if ($subscriptionChangeRequest->requestedPlan?->status !== 'active') {
            return back()->with('error', 'The requested plan is no longer active.');
        }

        if ($subscriptionChangeRequest->requiresPayment() && ! in_array($subscriptionChangeRequest->payment?->status, ['verified', 'received', 'paid'], true)) {
            return back()->with('error', 'Verify the subscription payment before approving this plan change.');
        }

        if ($this->hasBlockingUsage((int) $subscriptionChangeRequest->company_id, $subscriptionChangeRequest->requestedPlan)) {
            return back()->with('error', 'Company usage exceeds the requested plan limits.');
        }

        DB::transaction(function () use ($request, $subscriptionChangeRequest, $data): void {
            $subscription = $subscriptionChangeRequest->currentSubscription;
            $old = $subscription->only(['subscription_plan_id', 'status', 'starts_at', 'renews_at', 'ends_at', 'monthly_price']);
            $months = $subscriptionChangeRequest->billing_cycle === 'yearly' ? 12 : 1;
            $startsAt = today();

            $subscription->update([
                'subscription_plan_id' => $subscriptionChangeRequest->requested_plan_id,
                'status' => 'active',
                'starts_at' => $startsAt,
                'renews_at' => $startsAt->copy()->addMonths($months)->toDateString(),
                'ends_at' => null,
                'monthly_price' => $subscriptionChangeRequest->billing_cycle === 'yearly'
                    ? round(((float) $subscriptionChangeRequest->requested_price) / 12, 2)
                    : $subscriptionChangeRequest->requested_price,
            ]);

            $subscriptionChangeRequest->update([
                'status' => 'completed',
                'review_note' => $data['review_note'] ?? null,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'effective_date' => $startsAt,
            ]);

            $this->audit($request, $subscriptionChangeRequest, 'subscription_change_approved', 'Plan-change request approved and subscription activated.', $old, $subscription->fresh()->only(['subscription_plan_id', 'status', 'starts_at', 'renews_at', 'ends_at', 'monthly_price']));
        });

        $this->notifyCompanyAdmins($subscriptionChangeRequest->fresh(['requestedPlan', 'company.users']), 'Plan change approved', 'Your new subscription plan is now active.');

        return back()->with('success', 'Plan-change request approved and activated.');
    }

    public function reject(Request $request, SubscriptionChangeRequest $subscriptionChangeRequest): RedirectResponse
    {
        if (! in_array($subscriptionChangeRequest->status, ['pending', 'payment_required', 'payment_submitted', 'under_review'], true)) {
            return back()->with('error', 'This request cannot be rejected.');
        }

        $data = $request->validate(['review_note' => ['required', 'string', 'max:2000']]);

        DB::transaction(function () use ($request, $subscriptionChangeRequest, $data): void {
            $old = $subscriptionChangeRequest->only(['status', 'review_note', 'reviewed_by', 'reviewed_at']);
            $subscriptionChangeRequest->update([
                'status' => 'rejected',
                'review_note' => $data['review_note'],
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);

            $this->audit($request, $subscriptionChangeRequest, 'subscription_change_rejected', 'Plan-change request rejected.', $old, $subscriptionChangeRequest->fresh()->only(['status', 'review_note', 'reviewed_by', 'reviewed_at']));
        });

        $this->notifyCompanyAdmins($subscriptionChangeRequest->fresh(['requestedPlan', 'company.users']), 'Plan change rejected', 'Your plan-change request was rejected: '.$data['review_note']);

        return back()->with('success', 'Plan-change request rejected.');
    }

    private function notifyCompanyAdmins(SubscriptionChangeRequest $changeRequest, string $title, string $message): void
    {
        $changeRequest->company?->users->where('role', 'company_admin')->each(function (User $user) use ($changeRequest, $title, $message): void {
            try {
                $user->notify(new SubscriptionChangeStatusNotification($changeRequest, $title, $message));
            } catch (\Throwable $exception) {
                Log::warning('Subscription change notification failed.', ['error' => $exception->getMessage()]);
            }
        });
    }

    private function usage(int $companyId): array
    {
        return [
            'employees' => User::where('company_id', $companyId)->where('role', 'employee')->where('status', 'active')->count(),
            'projects' => Project::where('company_id', $companyId)->whereNotIn('status', ['cancelled'])->count(),
            'storage_mb' => 0,
        ];
    }

    private function hasBlockingUsage(int $companyId, SubscriptionPlan $plan): bool
    {
        $usage = $this->usage($companyId);

        return $usage['employees'] > (int) $plan->employee_limit
            || $usage['projects'] > (int) $plan->project_limit
            || $usage['storage_mb'] > (int) $plan->storage_limit_mb;
    }

    private function audit(Request $request, SubscriptionChangeRequest $changeRequest, string $action, string $description, array $old, array $new): void
    {
        AuditLog::create([
            'company_id' => $changeRequest->company_id,
            'user_id' => auth()->id(),
            'action' => $action,
            'module' => 'Subscriptions',
            'auditable_type' => SubscriptionChangeRequest::class,
            'auditable_id' => $changeRequest->id,
            'description' => $description,
            'old_values' => $old,
            'new_values' => $new,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
