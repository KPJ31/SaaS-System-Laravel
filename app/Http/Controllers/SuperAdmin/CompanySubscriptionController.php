<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanySubscriptionController extends Controller
{
    public function index(Request $request): View
    {
        $subscriptions = Subscription::with(['company', 'plan'])
            ->when($request->filled('search'), function ($query) use ($request): void {
                $query->whereHas('company', fn ($company) => $company->where('name', 'like', '%'.$request->search.'%'));
            })
            ->when($request->filled('plan'), fn ($query) => $query->where('subscription_plan_id', $request->plan))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('super-admin.subscriptions.index', [
            'subscriptions' => $subscriptions,
            'plans' => SubscriptionPlan::orderBy('name')->get(),
            'statuses' => ['trialing', 'active', 'expired', 'cancelled', 'suspended'],
        ]);
    }

    public function show(Subscription $subscription): View
    {
        return view('super-admin.subscriptions.show', [
            'subscription' => $subscription->load(['company.users', 'plan', 'payments']),
            'plans' => SubscriptionPlan::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Subscription $subscription): RedirectResponse
    {
        $data = $request->validate([
            'subscription_plan_id' => ['required', 'exists:subscription_plans,id'],
            'starts_at' => ['required', 'date'],
            'trial_ends_at' => ['nullable', 'date'],
            'renews_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
            'monthly_price' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:trialing,active,expired,cancelled,suspended'],
        ]);

        $old = $subscription->only(array_keys($data));
        $subscription->update($data);
        $this->audit('subscription_updated', 'Subscriptions', $subscription, $old, $data);

        return redirect()->route('super-admin.subscriptions.show', $subscription)->with('success', 'Subscription updated.');
    }

    public function updateStatus(Subscription $subscription, string $status): RedirectResponse
    {
        abort_unless(in_array($status, ['active', 'expired', 'cancelled', 'suspended'], true), 404);

        $old = ['status' => $subscription->status];
        $subscription->update(['status' => $status]);
        $this->audit('subscription_'.$status, 'Subscriptions', $subscription, $old, ['status' => $status]);

        return back()->with('success', 'Subscription status updated.');
    }

    private function audit(string $action, string $module, Subscription $subscription, array $old, array $new): void
    {
        AuditLog::create([
            'company_id' => $subscription->company_id,
            'user_id' => auth()->id(),
            'action' => $action,
            'module' => $module,
            'auditable_type' => Subscription::class,
            'auditable_id' => $subscription->id,
            'description' => 'Updated subscription for '.$subscription->company?->name,
            'old_values' => $old,
            'new_values' => $new,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
