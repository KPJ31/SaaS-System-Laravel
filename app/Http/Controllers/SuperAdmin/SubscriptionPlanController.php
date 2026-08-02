<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubscriptionPlanRequest;
use App\Models\SubscriptionPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SubscriptionPlanController extends Controller
{
    public function index(): View
    {
        return view('super-admin.subscription-plans.index', [
            'plans' => SubscriptionPlan::withCount('subscriptions')->orderBy('display_order')->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('super-admin.subscription-plans.form', [
            'plan' => new SubscriptionPlan(['status' => 'active', 'display_order' => 0, 'trial_days' => 14]),
        ]);
    }

    public function store(StoreSubscriptionPlanRequest $request): RedirectResponse
    {
        SubscriptionPlan::create($request->validated());

        return redirect()->route('super-admin.subscription-plans.index')->with('success', 'Subscription plan created successfully.');
    }

    public function edit(SubscriptionPlan $subscriptionPlan): View
    {
        return view('super-admin.subscription-plans.form', ['plan' => $subscriptionPlan]);
    }

    public function show(SubscriptionPlan $subscriptionPlan): View
    {
        return view('super-admin.subscription-plans.show', [
            'plan' => $subscriptionPlan->loadCount('subscriptions')->load(['subscriptions.company']),
        ]);
    }

    public function update(StoreSubscriptionPlanRequest $request, SubscriptionPlan $subscriptionPlan): RedirectResponse
    {
        $subscriptionPlan->update($request->validated());

        return redirect()->route('super-admin.subscription-plans.index')->with('success', 'Subscription plan updated successfully.');
    }

    public function updateStatus(SubscriptionPlan $subscriptionPlan, string $status): RedirectResponse
    {
        abort_unless(in_array($status, ['active', 'inactive'], true), 404);

        $subscriptionPlan->update(['status' => $status]);

        return back()->with('success', 'Subscription plan status updated.');
    }
}
