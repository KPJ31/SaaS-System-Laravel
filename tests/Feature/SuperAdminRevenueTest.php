<?php

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

function revenueUser(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'password' => Hash::make('Password@123'),
        'role' => 'super_admin',
        'status' => 'active',
    ], $attributes));
}

function revenueCompany(array $attributes = []): Company
{
    return Company::create(array_merge([
        'name' => fake()->unique()->company(),
        'slug' => fake()->unique()->slug(),
        'email' => fake()->unique()->safeEmail(),
        'phone' => '+1 555 0100',
        'address' => '100 Revenue Street',
        'status' => 'active',
    ], $attributes));
}

function revenuePlan(array $attributes = []): SubscriptionPlan
{
    return SubscriptionPlan::create(array_merge([
        'name' => fake()->unique()->word().' Plan',
        'slug' => fake()->unique()->slug(),
        'monthly_price' => 100,
        'annual_price' => 1000,
        'employee_limit' => 10,
        'client_limit' => 20,
        'project_limit' => 20,
        'storage_limit_mb' => 2048,
        'trial_days' => 14,
        'status' => 'active',
        'display_order' => 1,
    ], $attributes));
}

function revenuePayment(array $attributes = []): Payment
{
    $company = $attributes['company'] ?? revenueCompany();
    $plan = $attributes['plan'] ?? revenuePlan();
    unset($attributes['company'], $attributes['plan']);

    $subscription = Subscription::create([
        'company_id' => $company->id,
        'subscription_plan_id' => $plan->id,
        'status' => 'active',
        'starts_at' => now()->subMonth()->toDateString(),
        'renews_at' => now()->addMonth()->toDateString(),
        'monthly_price' => $plan->monthly_price,
    ]);

    return Payment::create(array_merge([
        'company_id' => $company->id,
        'subscription_id' => $subscription->id,
        'subscription_plan_id' => $plan->id,
        'transaction_reference' => fake()->unique()->bothify('SUB-REV-###'),
        'payment_type' => 'subscription',
        'amount' => 100,
        'method' => 'bank_transfer',
        'status' => 'verified',
        'verified_at' => now(),
        'paid_at' => now()->toDateString(),
    ], $attributes));
}

test('super admin revenue route is registered with the expected URL and middleware', function () {
    expect(Route::has('super-admin.revenue.index'))->toBeTrue()
        ->and(route('super-admin.revenue.index', absolute: false))->toBe('/super-admin/revenue')
        ->and(Route::getRoutes()->getByName('super-admin.revenue.index')->uri())->toBe('super-admin/revenue');
});

test('super admin can open revenue overview and sidebar link points to valid route', function () {
    SystemSetting::put('currency', 'USD');
    $admin = revenueUser(['email' => 'revenue-admin@example.test', 'username' => 'revenueadmin']);
    revenuePayment(['transaction_reference' => 'SUB-REV-OPEN']);

    $this->actingAs($admin)
        ->get(route('super-admin.revenue.index'))
        ->assertOk()
        ->assertSee('Revenue Overview')
        ->assertSee('SUB-REV-OPEN')
        ->assertSee(route('super-admin.revenue.index', absolute: false))
        ->assertSee('data-chart="revenueMonthlyTrend"', false);
});

test('revenue overview access is limited to super admins', function () {
    $company = revenueCompany();

    $this->get(route('super-admin.revenue.index'))
        ->assertRedirect(route('login'));

    $this->actingAs(revenueUser(['company_id' => $company->id, 'role' => 'company_admin']))
        ->get(route('super-admin.revenue.index'))
        ->assertForbidden();

    $this->actingAs(revenueUser(['company_id' => $company->id, 'role' => 'employee']))
        ->get(route('super-admin.revenue.index'))
        ->assertForbidden();
});

test('recognized revenue excludes pending payments', function () {
    SystemSetting::put('currency', 'USD');
    $admin = revenueUser(['email' => 'recognized-admin@example.test', 'username' => 'recognizedadmin']);
    $company = revenueCompany(['name' => 'Recognized Company']);
    $plan = revenuePlan(['name' => 'Recognized Plan', 'slug' => 'recognized-plan']);

    revenuePayment(['company' => $company, 'plan' => $plan, 'amount' => 100, 'status' => 'verified', 'transaction_reference' => 'SUB-VERIFIED']);
    revenuePayment(['company' => $company, 'plan' => $plan, 'amount' => 999, 'status' => 'pending', 'verified_at' => null, 'transaction_reference' => 'SUB-PENDING']);

    $this->actingAs($admin)
        ->get(route('super-admin.revenue.index', ['period' => 'custom']))
        ->assertOk()
        ->assertSee('Total Platform Revenue')
        ->assertSee('USD 100.00')
        ->assertSee('USD 999.00')
        ->assertDontSee('USD 1,099.00');
});

test('revenue filters by date company subscription plan and paginates', function () {
    $admin = revenueUser(['email' => 'filter-admin@example.test', 'username' => 'filteradmin']);
    $visibleCompany = revenueCompany(['name' => 'Visible Revenue Co']);
    $hiddenCompany = revenueCompany(['name' => 'Hidden Revenue Co']);
    $visiblePlan = revenuePlan(['name' => 'Scale Revenue', 'slug' => 'scale-revenue']);
    $hiddenPlan = revenuePlan(['name' => 'Starter Revenue', 'slug' => 'starter-revenue']);

    revenuePayment(['company' => $visibleCompany, 'plan' => $visiblePlan, 'transaction_reference' => 'SUB-FILTER-001', 'verified_at' => now()]);
    revenuePayment(['company' => $hiddenCompany, 'plan' => $hiddenPlan, 'transaction_reference' => 'SUB-HIDDEN-001', 'verified_at' => now()]);

    foreach (range(1, 12) as $index) {
        revenuePayment(['company' => $visibleCompany, 'plan' => $visiblePlan, 'transaction_reference' => 'SUB-PAGE-'.$index, 'verified_at' => now()]);
    }

    $this->actingAs($admin)
        ->get(route('super-admin.revenue.index', [
            'period' => 'custom',
            'date_from' => now()->subDay()->toDateString(),
            'date_to' => now()->addDay()->toDateString(),
            'company_id' => $visibleCompany->id,
            'plan_id' => $visiblePlan->id,
        ]))
        ->assertOk()
        ->assertSee('SUB-FILTER-001')
        ->assertDontSee('SUB-HIDDEN-001')
        ->assertSee('Showing');
});

test('revenue csv export preserves filters and creates audit log', function () {
    $admin = revenueUser(['email' => 'csv-revenue-admin@example.test', 'username' => 'csvrevenueadmin']);
    $visibleCompany = revenueCompany(['name' => 'CSV Visible Co']);
    $hiddenCompany = revenueCompany(['name' => 'CSV Hidden Co']);
    $plan = revenuePlan(['name' => 'CSV Plan', 'slug' => 'csv-plan']);

    revenuePayment(['company' => $visibleCompany, 'plan' => $plan, 'transaction_reference' => '=CSV-FORMULA']);
    revenuePayment(['company' => $hiddenCompany, 'plan' => $plan, 'transaction_reference' => 'CSV-HIDDEN']);

    $response = $this->actingAs($admin)
        ->get(route('super-admin.revenue.export.csv', [
            'period' => 'custom',
            'company_id' => $visibleCompany->id,
        ]));

    $response->assertOk();
    expect($response->streamedContent())
        ->toContain("'=CSV-FORMULA")
        ->toContain('CSV Visible Co')
        ->not->toContain('CSV-HIDDEN');

    expect(AuditLog::where('action', 'revenue_export_csv')->where('user_id', $admin->id)->exists())->toBeTrue();
});

test('revenue pdf export works and preserves filters', function () {
    $admin = revenueUser(['email' => 'pdf-revenue-admin@example.test', 'username' => 'pdfrevenueadmin']);
    $company = revenueCompany(['name' => 'PDF Revenue Co']);
    $plan = revenuePlan(['name' => 'PDF Plan', 'slug' => 'pdf-plan']);
    revenuePayment(['company' => $company, 'plan' => $plan, 'transaction_reference' => 'SUB-PDF-001']);

    $response = $this->actingAs($admin)
        ->get(route('super-admin.revenue.export.pdf', ['period' => 'custom', 'company_id' => $company->id]));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
    expect(AuditLog::where('action', 'revenue_export_pdf')->where('user_id', $admin->id)->exists())->toBeTrue();
});
