<?php

namespace App\Http\Controllers\CompanyAdmin;

use App\Http\Controllers\CompanyAdmin\Concerns\HandlesCompanyAccess;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\Project;
use App\Models\SubscriptionChangeRequest;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Notifications\SubscriptionChangeStatusNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SubscriptionChangeController extends Controller
{
    use HandlesCompanyAccess;

    public function index(): View
    {
        $company = $this->company()->load('activeSubscription.plan');
        $subscription = $company->activeSubscription;
        $plans = SubscriptionPlan::where('status', 'active')->orderBy('display_order')->orderBy('monthly_price')->get();
        $pendingRequest = SubscriptionChangeRequest::with(['currentPlan', 'requestedPlan', 'payment'])
            ->where('company_id', $this->companyId())
            ->whereIn('status', SubscriptionChangeRequest::ACTIVE_STATUSES)
            ->latest()
            ->first();
        $history = SubscriptionChangeRequest::with(['currentPlan', 'requestedPlan', 'reviewer'])
            ->where('company_id', $this->companyId())
            ->latest()
            ->paginate(8);

        return view('company-admin.subscriptions.index', [
            'company' => $company,
            'subscription' => $subscription,
            'plans' => $plans,
            'pendingRequest' => $pendingRequest,
            'history' => $history,
            'usage' => $this->usage(),
        ]);
    }

    public function create(SubscriptionPlan $plan, Request $request): View
    {
        abort_unless($plan->status === 'active', 404);

        $company = $this->company()->load('activeSubscription.plan');
        $subscription = $company->activeSubscription;
        abort_unless($subscription, 404);
        abort_if((int) $subscription->subscription_plan_id === (int) $plan->id, 422, 'This is already your current plan.');

        $billingCycle = $this->validBillingCycle($plan, $request->query('billing_cycle', 'monthly'));
        $summary = $this->summary($subscription->plan, $plan, $billingCycle);

        return view('company-admin.subscriptions.confirm', [
            'company' => $company,
            'subscription' => $subscription,
            'currentPlan' => $subscription->plan,
            'requestedPlan' => $plan,
            'summary' => $summary,
            'usage' => $this->usage(),
            'hasBlockingUsage' => $this->hasBlockingUsage($plan),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'requested_plan_id' => ['required', 'integer', 'exists:subscription_plans,id'],
            'billing_cycle' => ['required', Rule::in(['monthly', 'yearly'])],
            'request_note' => ['nullable', 'string', 'max:2000'],
            'terms' => ['accepted'],
        ]);

        $company = $this->company()->load('activeSubscription.plan');
        $subscription = $company->activeSubscription;
        if (! $subscription) {
            return back()->with('error', 'No active subscription was found for your company.');
        }

        $requestedPlan = SubscriptionPlan::where('status', 'active')->findOrFail($data['requested_plan_id']);
        if ((int) $requestedPlan->id === (int) $subscription->subscription_plan_id) {
            return back()->withErrors(['requested_plan_id' => 'Select a plan different from your current plan.']);
        }

        if ($data['billing_cycle'] === 'yearly' && $requestedPlan->annual_price === null) {
            return back()->withErrors(['billing_cycle' => 'Yearly billing is not available for this plan.']);
        }

        if ($this->hasActiveRequest()) {
            return back()->with('error', 'You already have a plan-change request in progress.');
        }

        if ($this->hasBlockingUsage($requestedPlan)) {
            return back()->with('error', 'Your current usage exceeds the selected plan limits. Reduce your usage or contact the Super Admin before requesting this downgrade.');
        }

        $billingCycle = $this->validBillingCycle($requestedPlan, $data['billing_cycle']);
        $summary = $this->summary($subscription->plan, $requestedPlan, $billingCycle);

        $changeRequest = DB::transaction(function () use ($request, $subscription, $requestedPlan, $summary, $billingCycle, $data): SubscriptionChangeRequest {
            $changeRequest = SubscriptionChangeRequest::create([
                'company_id' => $this->companyId(),
                'current_subscription_id' => $subscription->id,
                'current_plan_id' => $subscription->subscription_plan_id,
                'requested_plan_id' => $requestedPlan->id,
                'requested_by' => auth()->id(),
                'change_type' => $summary['change_type'],
                'current_price' => $summary['current_price'],
                'requested_price' => $summary['requested_price'],
                'payable_amount' => $summary['payable_amount'],
                'billing_cycle' => $billingCycle,
                'effective_date' => $summary['effective_date'],
                'status' => $summary['payable_amount'] > 0 ? 'payment_required' : 'pending',
                'request_note' => $data['request_note'] ?? null,
            ]);

            $this->audit('subscription_change_requested', 'Subscription plan change requested.', $changeRequest, [
                'current_plan' => $subscription->plan?->name,
                'requested_plan' => $requestedPlan->name,
                'payable_amount' => $summary['payable_amount'],
            ], $request);

            return $changeRequest;
        });

        $this->notifySuperAdmins($changeRequest, 'New plan-change request', $this->company()->name.' requested '.$requestedPlan->name.'.');

        return redirect()->route('company-admin.subscription.change.show', $changeRequest)->with('success', 'Plan-change request submitted.');
    }

    public function show(SubscriptionChangeRequest $changeRequest): View
    {
        $this->authorizeRequest($changeRequest);

        return view('company-admin.subscriptions.show', [
            'changeRequest' => $changeRequest->load(['currentPlan', 'requestedPlan', 'payment', 'reviewer']),
        ]);
    }

    public function payment(SubscriptionChangeRequest $changeRequest): View
    {
        $this->authorizeRequest($changeRequest);
        abort_unless($changeRequest->status === 'payment_required' || $changeRequest->status === 'payment_submitted', 404);

        return view('company-admin.subscriptions.payment', [
            'changeRequest' => $changeRequest->load(['requestedPlan', 'payment']),
        ]);
    }

    public function submitPayment(Request $request, SubscriptionChangeRequest $changeRequest): RedirectResponse
    {
        $this->authorizeRequest($changeRequest);
        if ($changeRequest->status !== 'payment_required') {
            return back()->with('error', 'Payment proof cannot be submitted for this request.');
        }

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:'.$changeRequest->payable_amount],
            'method' => ['required', 'string', 'max:80'],
            'transaction_reference' => ['required', 'string', 'max:120'],
            'paid_at' => ['required', 'date', 'before_or_equal:today'],
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $changeRequest, $data): void {
            $path = $request->file('proof')->store('subscription-proofs', 'public');
            $payment = Payment::create([
                'company_id' => $this->companyId(),
                'subscription_id' => $changeRequest->current_subscription_id,
                'subscription_plan_id' => $changeRequest->requested_plan_id,
                'created_by' => auth()->id(),
                'transaction_reference' => $data['transaction_reference'],
                'payment_type' => 'subscription',
                'amount' => $changeRequest->payable_amount,
                'method' => $data['method'],
                'proof_path' => $path,
                'status' => 'submitted',
                'paid_at' => $data['paid_at'],
                'notes' => $data['notes'] ?? null,
            ]);

            $changeRequest->update(['payment_id' => $payment->id, 'status' => 'payment_submitted']);
            $this->audit('subscription_change_payment_submitted', 'Payment proof submitted for plan change.', $changeRequest, ['payment_id' => $payment->id], $request);
        });

        $this->notifySuperAdmins($changeRequest->fresh(['requestedPlan']), 'Plan-change payment submitted', $this->company()->name.' submitted payment proof.');

        return redirect()->route('company-admin.subscription.change.show', $changeRequest)->with('success', 'Payment proof submitted for Super Admin review.');
    }

    public function cancel(Request $request, SubscriptionChangeRequest $changeRequest): RedirectResponse
    {
        $this->authorizeRequest($changeRequest);
        if (! $changeRequest->canBeCancelled()) {
            return back()->with('error', 'This request can no longer be cancelled.');
        }

        $data = $request->validate(['cancellation_reason' => ['nullable', 'string', 'max:1000']]);
        $changeRequest->update([
            'status' => 'cancelled',
            'cancelled_by' => auth()->id(),
            'cancelled_at' => now(),
            'cancellation_reason' => $data['cancellation_reason'] ?? null,
        ]);
        $this->audit('subscription_change_cancelled', 'Plan-change request cancelled.', $changeRequest, $data, $request);

        return redirect()->route('company-admin.subscription.index')->with('success', 'Plan-change request cancelled.');
    }

    private function summary(SubscriptionPlan $currentPlan, SubscriptionPlan $requestedPlan, string $billingCycle): array
    {
        $currentPrice = $billingCycle === 'yearly' ? ($currentPlan->annual_price ?? $currentPlan->monthly_price * 12) : $currentPlan->monthly_price;
        $requestedPrice = $billingCycle === 'yearly' ? ($requestedPlan->annual_price ?? $requestedPlan->monthly_price * 12) : $requestedPlan->monthly_price;
        $changeType = $requestedPrice > $currentPrice ? 'upgrade' : ($requestedPrice < $currentPrice ? 'downgrade' : 'lateral_change');

        return [
            'current_price' => (float) $currentPrice,
            'requested_price' => (float) $requestedPrice,
            'payable_amount' => $changeType === 'downgrade' ? 0 : (float) $requestedPrice,
            'change_type' => $changeType,
            'effective_date' => $changeType === 'downgrade' ? $this->company()->activeSubscription?->renews_at ?? today() : today(),
        ];
    }

    private function validBillingCycle(SubscriptionPlan $plan, string $billingCycle): string
    {
        if ($billingCycle === 'yearly' && $plan->annual_price !== null) {
            return 'yearly';
        }

        return 'monthly';
    }

    private function usage(): array
    {
        return [
            'employees' => User::where('company_id', $this->companyId())->where('role', 'employee')->where('status', 'active')->count(),
            'projects' => Project::where('company_id', $this->companyId())->whereNotIn('status', ['cancelled'])->count(),
            'storage_mb' => 0,
        ];
    }

    private function hasBlockingUsage(SubscriptionPlan $plan): bool
    {
        $usage = $this->usage();

        return $usage['employees'] > (int) $plan->employee_limit
            || $usage['projects'] > (int) $plan->project_limit
            || $usage['storage_mb'] > (int) $plan->storage_limit_mb;
    }

    private function hasActiveRequest(): bool
    {
        return SubscriptionChangeRequest::where('company_id', $this->companyId())
            ->whereIn('status', SubscriptionChangeRequest::ACTIVE_STATUSES)
            ->exists();
    }

    private function authorizeRequest(SubscriptionChangeRequest $changeRequest): void
    {
        abort_unless((int) $changeRequest->company_id === $this->companyId(), 403);
    }

    private function notifySuperAdmins(SubscriptionChangeRequest $changeRequest, string $title, string $message): void
    {
        User::where('role', 'super_admin')->get()->each(function (User $user) use ($changeRequest, $title, $message): void {
            try {
                $user->notify(new SubscriptionChangeStatusNotification($changeRequest, $title, $message));
            } catch (\Throwable $exception) {
                Log::warning('Subscription change notification failed.', ['error' => $exception->getMessage()]);
            }
        });
    }

    private function audit(string $action, string $description, SubscriptionChangeRequest $changeRequest, array $metadata, Request $request): void
    {
        AuditLog::create([
            'company_id' => $this->companyId(),
            'user_id' => auth()->id(),
            'action' => $action,
            'module' => 'Subscriptions',
            'auditable_type' => SubscriptionChangeRequest::class,
            'auditable_id' => $changeRequest->id,
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
