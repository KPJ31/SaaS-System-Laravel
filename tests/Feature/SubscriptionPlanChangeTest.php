<?php

use App\Models\Company;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionChangeRequest;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

function planChangeCompany(string $name = 'Acme Soft'): Company
{
    return Company::create([
        'name' => $name,
        'slug' => strtolower(str_replace(' ', '-', $name)).'-'.uniqid(),
        'email' => strtolower(str_replace(' ', '', $name)).'@example.test',
        'timezone' => 'UTC',
        'date_format' => 'Y-m-d',
        'status' => 'active',
    ]);
}

function planChangePlan(string $name, float $monthly, int $employees = 10, int $projects = 10): SubscriptionPlan
{
    return SubscriptionPlan::create([
        'name' => $name,
        'slug' => strtolower($name).'-'.uniqid(),
        'monthly_price' => $monthly,
        'annual_price' => $monthly * 10,
        'employee_limit' => $employees,
        'client_limit' => 10,
        'project_limit' => $projects,
        'storage_limit_mb' => 1024,
        'trial_days' => 0,
        'features' => ['Feature A', 'Feature B'],
        'status' => 'active',
        'display_order' => (int) $monthly,
    ]);
}

function planChangeAdmin(Company $company, string $role = 'company_admin'): User
{
    return User::factory()->create(['company_id' => $company->id, 'role' => $role, 'status' => 'active']);
}

function planChangeSubscription(Company $company, SubscriptionPlan $plan): Subscription
{
    return Subscription::create([
        'company_id' => $company->id,
        'subscription_plan_id' => $plan->id,
        'status' => 'active',
        'starts_at' => now()->subMonth()->toDateString(),
        'renews_at' => now()->addMonth()->toDateString(),
        'monthly_price' => $plan->monthly_price,
    ]);
}

test('company admin can view current subscription and active plans', function () {
    $company = planChangeCompany();
    $starter = planChangePlan('Starter', 20);
    $pro = planChangePlan('Pro', 60);
    planChangeSubscription($company, $starter);

    $this->actingAs(planChangeAdmin($company))
        ->get(route('company-admin.subscription.index'))
        ->assertOk()
        ->assertSee('Current Subscription')
        ->assertSee('Starter')
        ->assertSee('Pro')
        ->assertSee('Select Plan');
});

test('employee cannot access company admin plan change routes', function () {
    $company = planChangeCompany();
    $starter = planChangePlan('Starter', 20);
    $pro = planChangePlan('Pro', 60);
    planChangeSubscription($company, $starter);

    $this->actingAs(planChangeAdmin($company, 'employee'))
        ->get(route('company-admin.subscription.change.create', $pro))
        ->assertForbidden();
});

test('valid plan change request uses server-side amount and blocks duplicate pending requests', function () {
    Notification::fake();
    $company = planChangeCompany();
    $starter = planChangePlan('Starter', 20);
    $pro = planChangePlan('Pro', 60);
    planChangeSubscription($company, $starter);
    $admin = planChangeAdmin($company);

    $this->actingAs($admin)->post(route('company-admin.subscription.change.store'), [
        'requested_plan_id' => $pro->id,
        'billing_cycle' => 'monthly',
        'payable_amount' => 1,
        'terms' => '1',
    ])->assertRedirect();

    $this->assertDatabaseHas('subscription_change_requests', [
        'company_id' => $company->id,
        'current_plan_id' => $starter->id,
        'requested_plan_id' => $pro->id,
        'payable_amount' => '60',
        'status' => 'payment_required',
    ]);

    $this->actingAs($admin)->post(route('company-admin.subscription.change.store'), [
        'requested_plan_id' => $pro->id,
        'billing_cycle' => 'monthly',
        'terms' => '1',
    ])->assertSessionHas('error', 'You already have a plan-change request in progress.');
});

test('cross company request access is blocked and pending request can be cancelled', function () {
    $company = planChangeCompany('Acme One');
    $otherCompany = planChangeCompany('Acme Two');
    $starter = planChangePlan('Starter', 20);
    $pro = planChangePlan('Pro', 60);
    $subscription = planChangeSubscription($company, $starter);
    planChangeSubscription($otherCompany, $starter);
    $request = SubscriptionChangeRequest::create([
        'company_id' => $company->id,
        'current_subscription_id' => $subscription->id,
        'current_plan_id' => $starter->id,
        'requested_plan_id' => $pro->id,
        'requested_by' => planChangeAdmin($company)->id,
        'change_type' => 'upgrade',
        'current_price' => 20,
        'requested_price' => 60,
        'payable_amount' => 60,
        'billing_cycle' => 'monthly',
        'status' => 'payment_required',
    ]);

    $this->actingAs(planChangeAdmin($otherCompany))
        ->get(route('company-admin.subscription.change.show', $request))
        ->assertForbidden();

    $this->actingAs(User::find($request->requested_by))
        ->post(route('company-admin.subscription.change.cancel', $request), ['cancellation_reason' => 'Changed mind'])
        ->assertRedirect(route('company-admin.subscription.index'));

    expect($request->fresh()->status)->toBe('cancelled');
});

test('payment proof is stored as subscription payment for the change request', function () {
    Storage::fake('public');
    Notification::fake();
    $company = planChangeCompany();
    $starter = planChangePlan('Starter', 20);
    $pro = planChangePlan('Pro', 60);
    $subscription = planChangeSubscription($company, $starter);
    $admin = planChangeAdmin($company);
    $request = SubscriptionChangeRequest::create([
        'company_id' => $company->id,
        'current_subscription_id' => $subscription->id,
        'current_plan_id' => $starter->id,
        'requested_plan_id' => $pro->id,
        'requested_by' => $admin->id,
        'change_type' => 'upgrade',
        'current_price' => 20,
        'requested_price' => 60,
        'payable_amount' => 60,
        'billing_cycle' => 'monthly',
        'status' => 'payment_required',
    ]);

    $this->actingAs($admin)->post(route('company-admin.subscription.change.payment.store', $request), [
        'amount' => 60,
        'method' => 'bank_transfer',
        'transaction_reference' => 'BANK-123',
        'paid_at' => now()->toDateString(),
        'proof' => UploadedFile::fake()->image('proof.jpg'),
    ])->assertRedirect(route('company-admin.subscription.change.show', $request));

    $payment = Payment::first();
    expect($payment->payment_type)->toBe('subscription')
        ->and($request->fresh()->status)->toBe('payment_submitted')
        ->and($request->fresh()->payment_id)->toBe($payment->id);
});

test('super admin approval requires verified payment and activates requested plan after verification', function () {
    Notification::fake();
    $company = planChangeCompany();
    $starter = planChangePlan('Starter', 20);
    $pro = planChangePlan('Pro', 60);
    $subscription = planChangeSubscription($company, $starter);
    $admin = planChangeAdmin($company);
    $superAdmin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
    $payment = Payment::create([
        'company_id' => $company->id,
        'subscription_id' => $subscription->id,
        'subscription_plan_id' => $pro->id,
        'created_by' => $admin->id,
        'payment_type' => 'subscription',
        'amount' => 60,
        'method' => 'bank_transfer',
        'status' => 'submitted',
    ]);
    $request = SubscriptionChangeRequest::create([
        'company_id' => $company->id,
        'current_subscription_id' => $subscription->id,
        'current_plan_id' => $starter->id,
        'requested_plan_id' => $pro->id,
        'requested_by' => $admin->id,
        'change_type' => 'upgrade',
        'current_price' => 20,
        'requested_price' => 60,
        'payable_amount' => 60,
        'billing_cycle' => 'monthly',
        'status' => 'payment_submitted',
        'payment_id' => $payment->id,
    ]);

    $this->actingAs($superAdmin)
        ->post(route('super-admin.subscription-change-requests.approve', $request))
        ->assertSessionHas('error', 'Verify the subscription payment before approving this plan change.');

    $payment->update(['status' => 'verified']);

    $this->actingAs($superAdmin)
        ->post(route('super-admin.subscription-change-requests.approve', $request), ['review_note' => 'Approved'])
        ->assertSessionHas('success', 'Plan-change request approved and activated.');

    expect($subscription->fresh()->subscription_plan_id)->toBe($pro->id)
        ->and($request->fresh()->status)->toBe('completed');
});

test('downgrade is blocked when current usage exceeds requested plan limits', function () {
    $company = planChangeCompany();
    $pro = planChangePlan('Pro', 60, employees: 10);
    $starter = planChangePlan('Starter', 20, employees: 1);
    planChangeSubscription($company, $pro);
    $admin = planChangeAdmin($company);
    planChangeAdmin($company, 'employee');
    planChangeAdmin($company, 'employee');

    $this->actingAs($admin)->post(route('company-admin.subscription.change.store'), [
        'requested_plan_id' => $starter->id,
        'billing_cycle' => 'monthly',
        'terms' => '1',
    ])->assertSessionHas('error', 'Your current usage exceeds the selected plan limits. Reduce your usage or contact the Super Admin before requesting this downgrade.');
});
