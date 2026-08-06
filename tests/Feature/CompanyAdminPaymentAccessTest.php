<?php

use App\Models\Company;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

function companyPaymentAccessCompany(array $attributes = []): Company
{
    return Company::create(array_merge([
        'name' => fake()->unique()->company(),
        'slug' => fake()->unique()->slug(),
        'email' => fake()->unique()->companyEmail(),
        'status' => 'active',
    ], $attributes));
}

function companyPaymentAccessPlan(array $attributes = []): SubscriptionPlan
{
    return SubscriptionPlan::create(array_merge([
        'name' => fake()->unique()->word().' Plan',
        'slug' => fake()->unique()->slug(),
        'monthly_price' => 49,
        'annual_price' => 499,
        'employee_limit' => 10,
        'client_limit' => 25,
        'project_limit' => 20,
        'storage_limit_mb' => 2048,
        'trial_days' => 14,
        'status' => 'active',
        'display_order' => 1,
    ], $attributes));
}

function companyPaymentAccessAdmin(?Company $company = null, array $attributes = []): User
{
    $company ??= companyPaymentAccessCompany();
    $plan = companyPaymentAccessPlan();

    Subscription::create([
        'company_id' => $company->id,
        'subscription_plan_id' => $plan->id,
        'status' => 'active',
        'starts_at' => now()->toDateString(),
        'renews_at' => now()->addMonth()->toDateString(),
        'monthly_price' => $plan->monthly_price,
    ]);

    return User::factory()->create(array_merge([
        'company_id' => $company->id,
        'role' => 'company_admin',
        'status' => 'active',
        'password' => Hash::make('Password@123'),
    ], $attributes));
}

test('company admin payment list only shows client project payments', function () {
    $admin = companyPaymentAccessAdmin();

    Payment::create([
        'company_id' => $admin->company_id,
        'transaction_reference' => 'CLIENT-PAY-001',
        'payment_type' => 'client_project',
        'amount' => 250,
        'method' => 'bank_transfer',
        'status' => 'requested',
    ]);

    Payment::create([
        'company_id' => $admin->company_id,
        'transaction_reference' => 'SUB-PAY-001',
        'payment_type' => 'subscription',
        'amount' => 49,
        'method' => 'bank_transfer',
        'status' => 'submitted',
    ]);

    $this->actingAs($admin)
        ->get(route('company-admin.payments.index'))
        ->assertOk()
        ->assertSee('CLIENT-PAY-001')
        ->assertDontSee('SUB-PAY-001');
});

test('company admin cannot directly view or update subscription payments', function () {
    $admin = companyPaymentAccessAdmin();

    $payment = Payment::create([
        'company_id' => $admin->company_id,
        'transaction_reference' => 'SUB-DIRECT-001',
        'payment_type' => 'subscription',
        'amount' => 49,
        'method' => 'bank_transfer',
        'status' => 'submitted',
    ]);

    $this->actingAs($admin)
        ->get(route('company-admin.payments.show', $payment))
        ->assertNotFound();

    $this->actingAs($admin)
        ->post(route('company-admin.payments.verify', $payment), ['verification_note' => 'Wrong module.'])
        ->assertNotFound();

    $this->actingAs($admin)
        ->patch(route('company-admin.payments.update', $payment), [
            'amount' => 99,
            'method' => 'bank_transfer',
            'status' => 'paid',
        ])
        ->assertNotFound();

    $this->assertDatabaseHas('payments', [
        'id' => $payment->id,
        'status' => 'submitted',
        'verified_by' => null,
    ]);
});
