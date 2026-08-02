<?php

use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

function companyAdminTestCompany(array $attributes = []): Company
{
    return Company::create(array_merge([
        'name' => fake()->company(),
        'slug' => fake()->unique()->slug(),
        'email' => fake()->unique()->companyEmail(),
        'status' => 'active',
    ], $attributes));
}

function companyAdminTestPlan(array $attributes = []): SubscriptionPlan
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

function companyAdminTestAdmin(?Company $company = null, array $attributes = []): User
{
    $company ??= companyAdminTestCompany();
    $plan = companyAdminTestPlan();

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

test('company admin dashboard is accessible with active subscription', function () {
    $admin = companyAdminTestAdmin();

    $this->actingAs($admin)
        ->get(route('company-admin.dashboard'))
        ->assertOk()
        ->assertSee('Welcome back');
});

test('employee cannot access company admin dashboard', function () {
    $company = companyAdminTestCompany();
    companyAdminTestAdmin($company);
    $employee = User::factory()->create(['company_id' => $company->id, 'role' => 'employee', 'status' => 'active']);

    $this->actingAs($employee)
        ->get(route('company-admin.dashboard'))
        ->assertForbidden();
});

test('company admin cannot view another company client', function () {
    $admin = companyAdminTestAdmin();
    $otherCompany = companyAdminTestCompany();
    $client = Client::create(['company_id' => $otherCompany->id, 'name' => 'Hidden Client']);

    $this->actingAs($admin)
        ->get(route('company-admin.clients.show', $client))
        ->assertForbidden();
});

test('company admin can create employee under own company', function () {
    $admin = companyAdminTestAdmin();

    $this->actingAs($admin)
        ->post(route('company-admin.employees.store'), [
            'name' => 'New Employee',
            'email' => 'new-employee@example.test',
            'username' => 'new_employee',
            'status' => 'active',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
        ])
        ->assertRedirect(route('company-admin.employees.index'));

    $this->assertDatabaseHas('users', [
        'company_id' => $admin->company_id,
        'email' => 'new-employee@example.test',
        'role' => 'employee',
    ]);
});

test('company admin can create project and task using own company records', function () {
    $admin = companyAdminTestAdmin();
    $client = Client::create(['company_id' => $admin->company_id, 'name' => 'Project Client']);
    $employee = User::factory()->create(['company_id' => $admin->company_id, 'role' => 'employee', 'status' => 'active']);

    $this->actingAs($admin)
        ->post(route('company-admin.projects.store'), [
            'client_id' => $client->id,
            'manager_id' => $employee->id,
            'name' => 'Client Portal',
            'status' => 'planning',
            'priority' => 'medium',
            'progress' => 0,
        ])
        ->assertRedirect();

    $project = Project::where('name', 'Client Portal')->firstOrFail();

    $this->actingAs($admin)
        ->post(route('company-admin.tasks.store'), [
            'project_id' => $project->id,
            'assignee_id' => $employee->id,
            'title' => 'Build dashboard',
            'priority' => 'high',
            'status' => 'assigned',
            'progress' => 0,
            'task_type' => 'task',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('tasks', ['company_id' => $admin->company_id, 'title' => 'Build dashboard']);
});

test('company admin can verify client project payment', function () {
    $admin = companyAdminTestAdmin();
    $payment = Payment::create([
        'company_id' => $admin->company_id,
        'payment_type' => 'client_project',
        'amount' => 250,
        'method' => 'bank_transfer',
        'status' => 'proof_submitted',
    ]);

    $this->actingAs($admin)
        ->post(route('company-admin.payments.verify', $payment), ['verification_note' => 'Receipt matched.'])
        ->assertRedirect();

    $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'paid', 'verified_by' => $admin->id]);
});

test('company admin can create invoice', function () {
    $admin = companyAdminTestAdmin();
    $client = Client::create(['company_id' => $admin->company_id, 'name' => 'Invoice Client']);

    $this->actingAs($admin)
        ->post(route('company-admin.invoices.store'), [
            'client_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'subtotal' => 100,
            'tax' => 10,
            'total' => 110,
            'paid_amount' => 0,
            'status' => 'draft',
        ])
        ->assertRedirect();

    expect(Invoice::where('company_id', $admin->company_id)->where('total', 110)->exists())->toBeTrue();
});

test('employee subscription limit is enforced', function () {
    $company = companyAdminTestCompany();
    $plan = companyAdminTestPlan(['employee_limit' => 1]);
    Subscription::create([
        'company_id' => $company->id,
        'subscription_plan_id' => $plan->id,
        'status' => 'active',
        'starts_at' => now()->toDateString(),
        'renews_at' => now()->addMonth()->toDateString(),
        'monthly_price' => $plan->monthly_price,
    ]);
    $admin = User::factory()->create(['company_id' => $company->id, 'role' => 'company_admin', 'status' => 'active']);
    User::factory()->create(['company_id' => $company->id, 'role' => 'employee', 'status' => 'active']);

    $this->actingAs($admin)
        ->post(route('company-admin.employees.store'), [
            'name' => 'Blocked Employee',
            'email' => 'blocked@example.test',
            'status' => 'active',
        ])
        ->assertSessionHas('error', 'You have reached the limit of your current subscription plan.');
});

test('company admin can preview report before export', function () {
    $admin = companyAdminTestAdmin();
    User::factory()->create([
        'company_id' => $admin->company_id,
        'role' => 'employee',
        'status' => 'active',
        'name' => 'Report Employee',
    ]);

    $this->actingAs($admin)
        ->get(route('company-admin.reports.show', 'employees'))
        ->assertOk()
        ->assertSee('Employee Report')
        ->assertSee('Report Employee')
        ->assertSee('Download CSV')
        ->assertSee('Download PDF');
});

test('company admin can download report pdf', function () {
    $admin = companyAdminTestAdmin();
    User::factory()->create([
        'company_id' => $admin->company_id,
        'role' => 'employee',
        'status' => 'active',
        'name' => 'PDF Report Employee',
    ]);

    $response = $this->actingAs($admin)
        ->get(route('company-admin.reports.pdf', 'employees'));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});
