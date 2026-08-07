<?php

use App\Models\Client;
use App\Models\Company;
use App\Models\CompanyRegistrationRequest;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\SystemSetting;
use App\Models\Task;
use App\Models\User;
use App\Notifications\CompanyRegistrationApproved;
use App\Notifications\CompanyRegistrationReceived;
use App\Notifications\CompanyRegistrationRejected;
use App\Services\WorkTimerService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

function makeUser(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'password' => Hash::make('Password@123'),
        'role' => 'super_admin',
        'status' => 'active',
    ], $attributes));
}

function makeCompany(array $attributes = []): Company
{
    return Company::create(array_merge([
        'name' => 'Test Software Company',
        'slug' => fake()->unique()->slug(),
        'email' => fake()->unique()->safeEmail(),
        'phone' => '+1 555 0100',
        'address' => '100 Test Street',
        'status' => 'active',
    ], $attributes));
}

test('landing page is available', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Run your software company from one connected workspace.')
        ->assertSee('Core capabilities for software company operations.');
});

test('public informational pages are available', function () {
    $this->get(route('about'))->assertOk()->assertSee('About Elevanix');
    $this->get(route('contact'))->assertOk()->assertSee('Contact Elevanix');
    $this->get(route('privacy'))->assertOk()->assertSee('Privacy Policy');
    $this->get(route('terms'))->assertOk()->assertSee('Terms and Conditions');
});

test('user can login with email', function () {
    $user = makeUser(['email' => 'admin@example.test', 'username' => 'admin']);

    $this->post(route('login.store'), [
        'login' => 'admin@example.test',
        'password' => 'Password@123',
    ])->assertRedirect(route('super-admin.dashboard'));

    $this->assertAuthenticatedAs($user);
});

test('user can login with username', function () {
    $user = makeUser(['email' => 'owner@example.test', 'username' => 'owner']);

    $this->post(route('login.store'), [
        'login' => 'owner',
        'password' => 'Password@123',
    ])->assertRedirect(route('super-admin.dashboard'));

    $this->assertAuthenticatedAs($user);
});

test('super admin can access dashboard', function () {
    $user = makeUser(['email' => 'dashboard-admin@example.test', 'username' => 'dashboardadmin']);

    $this->actingAs($user)
        ->get(route('super-admin.dashboard'))
        ->assertOk()
        ->assertSee('Platform Dashboard');
});

test('inactive user cannot login', function () {
    makeUser(['email' => 'inactive@example.test', 'username' => 'inactive', 'status' => 'inactive']);

    $this->post(route('login.store'), [
        'login' => 'inactive@example.test',
        'password' => 'Password@123',
    ])->assertSessionHasErrors('login');

    $this->assertGuest();
});

test('password reset request page is available', function () {
    $this->get(route('password.request'))
        ->assertOk()
        ->assertSee('Forgot your password?');
});

test('new password page is available with token', function () {
    $this->get(route('password.reset', ['token' => 'sample-token', 'email' => 'admin@example.test']))
        ->assertOk()
        ->assertSee('Set a new password');
});

test('pending company user cannot login', function () {
    $company = makeCompany(['status' => 'pending']);
    makeUser([
        'company_id' => $company->id,
        'email' => 'pending-user@example.test',
        'username' => 'pendinguser',
        'role' => 'company_admin',
    ]);

    $this->post(route('login.store'), [
        'login' => 'pendinguser',
        'password' => 'Password@123',
    ])->assertSessionHasErrors('login');

    $this->assertGuest();
});

test('role middleware blocks company admin from super admin area', function () {
    $company = makeCompany();
    $user = makeUser(['company_id' => $company->id, 'role' => 'company_admin']);

    $this->actingAs($user)->get(route('super-admin.dashboard'))->assertForbidden();
});

test('role middleware blocks employee from super admin area', function () {
    $company = makeCompany();
    $user = makeUser(['company_id' => $company->id, 'role' => 'employee']);

    $this->actingAs($user)->get(route('super-admin.dashboard'))->assertForbidden();
});

test('company registration creates pending request and notifies super admin', function () {
    Notification::fake();
    $superAdmin = makeUser(['email' => 'super@elevanix.test', 'username' => 'super']);

    $this->post(route('company.register.store'), [
        'company_name' => 'FutureSoft',
        'company_email' => 'hello@futuresoft.test',
        'company_phone' => '+1 555 3333',
        'company_address' => '22 Market Road',
        'admin_name' => 'Future Admin',
        'admin_email' => 'admin@futuresoft.test',
        'username' => 'future_admin',
        'password' => 'Password@123',
        'password_confirmation' => 'Password@123',
        'terms' => '1',
    ])->assertRedirect(route('company.register.submitted'));

    $this->assertDatabaseHas('company_registration_requests', [
        'company_email' => 'hello@futuresoft.test',
        'status' => 'pending',
    ]);

    Notification::assertSentTo($superAdmin, CompanyRegistrationReceived::class);
});

test('super admin can approve company registration request', function () {
    Notification::fake();
    $superAdmin = makeUser(['email' => 'super@elevanix.test', 'username' => 'super']);
    $plan = SubscriptionPlan::create([
        'name' => 'Starter',
        'slug' => 'starter',
        'monthly_price' => 49,
        'annual_price' => 499,
        'employee_limit' => 10,
        'client_limit' => 25,
        'project_limit' => 20,
        'storage_limit_mb' => 2048,
        'trial_days' => 14,
        'status' => 'active',
        'display_order' => 1,
    ]);
    $request = CompanyRegistrationRequest::create([
        'company_name' => 'ApproveSoft',
        'company_email' => 'hello@approvesoft.test',
        'company_phone' => '+1 555 2222',
        'company_address' => '44 Approval Lane',
        'admin_name' => 'Approve Admin',
        'admin_email' => 'admin@approvesoft.test',
        'username' => 'approve_admin',
        'password' => Hash::make('Password@123'),
        'status' => 'pending',
    ]);

    $this->actingAs($superAdmin)
        ->post(route('super-admin.company-requests.approve', $request))
        ->assertRedirect(route('super-admin.company-requests.index'));

    $admin = User::where('email', 'admin@approvesoft.test')->first();

    $this->assertDatabaseHas('companies', ['email' => 'hello@approvesoft.test', 'status' => 'active']);
    $this->assertDatabaseHas('company_registration_requests', ['id' => $request->id, 'status' => 'approved']);
    $this->assertDatabaseHas('subscriptions', ['subscription_plan_id' => $plan->id, 'status' => 'trialing']);
    expect($admin)->not->toBeNull()->and($admin->role)->toBe('company_admin');
    Notification::assertSentTo($admin, CompanyRegistrationApproved::class);
});

test('super admin can reject company registration request', function () {
    Notification::fake();
    $superAdmin = makeUser(['email' => 'super@elevanix.test', 'username' => 'super']);
    $request = CompanyRegistrationRequest::create([
        'company_name' => 'RejectSoft',
        'company_email' => 'hello@rejectsoft.test',
        'company_phone' => '+1 555 2223',
        'company_address' => '45 Review Lane',
        'admin_name' => 'Reject Admin',
        'admin_email' => 'admin@rejectsoft.test',
        'username' => 'reject_admin',
        'password' => Hash::make('Password@123'),
        'status' => 'pending',
    ]);

    $this->actingAs($superAdmin)
        ->post(route('super-admin.company-requests.reject', $request), ['rejection_reason' => 'Company details could not be verified.'])
        ->assertRedirect(route('super-admin.company-requests.index'));

    $this->assertDatabaseHas('company_registration_requests', [
        'id' => $request->id,
        'status' => 'rejected',
    ]);
    Notification::assertSentOnDemand(CompanyRegistrationRejected::class);
});

test('super admin can create subscription plan', function () {
    $superAdmin = makeUser(['email' => 'super@elevanix.test', 'username' => 'super']);

    $this->actingAs($superAdmin)
        ->post(route('super-admin.subscription-plans.store'), [
            'name' => 'Scale',
            'slug' => 'scale',
            'description' => 'Plan for larger teams.',
            'monthly_price' => 199,
            'annual_price' => 1990,
            'employee_limit' => 100,
            'client_limit' => 200,
            'project_limit' => 200,
            'storage_limit_mb' => 51200,
            'trial_days' => 30,
            'features' => "Advanced reports\nPriority support",
            'status' => 'active',
            'display_order' => 3,
        ])
        ->assertRedirect(route('super-admin.subscription-plans.index'));

    $this->assertDatabaseHas('subscription_plans', [
        'slug' => 'scale',
        'monthly_price' => 199,
        'status' => 'active',
    ]);
});

test('super admin can suspend company', function () {
    $superAdmin = makeUser(['email' => 'super@elevanix.test', 'username' => 'super']);
    $company = makeCompany(['status' => 'active']);
    $admin = makeUser(['company_id' => $company->id, 'role' => 'company_admin', 'status' => 'active']);

    $this->actingAs($superAdmin)
        ->post(route('super-admin.companies.status', [$company, 'suspended']), ['reason' => 'Subscription payment is overdue.'])
        ->assertRedirect();

    $this->assertDatabaseHas('companies', ['id' => $company->id, 'status' => 'suspended']);
    $this->assertDatabaseHas('users', ['id' => $admin->id, 'status' => 'suspended']);
});

test('company suspension requires a reason', function () {
    $superAdmin = makeUser(['email' => 'reason-admin@example.test', 'username' => 'reasonadmin']);
    $company = makeCompany(['status' => 'active']);

    $this->actingAs($superAdmin)
        ->from(route('super-admin.companies.show', $company))
        ->post(route('super-admin.companies.status', [$company, 'suspended']))
        ->assertSessionHasErrors('reason');
});

test('duplicate company approval is blocked', function () {
    $superAdmin = makeUser(['email' => 'duplicate-admin@example.test', 'username' => 'duplicateadmin']);
    $company = makeCompany(['status' => 'active']);

    $this->actingAs($superAdmin)
        ->post(route('super-admin.companies.status', [$company, 'active']))
        ->assertSessionHas('error');
});

test('super admin can update subscription plan', function () {
    $superAdmin = makeUser(['email' => 'super@elevanix.test', 'username' => 'super']);
    $plan = SubscriptionPlan::create([
        'name' => 'Starter',
        'slug' => 'starter',
        'monthly_price' => 49,
        'annual_price' => 499,
        'employee_limit' => 10,
        'client_limit' => 25,
        'project_limit' => 20,
        'storage_limit_mb' => 2048,
        'trial_days' => 14,
        'status' => 'active',
        'display_order' => 1,
    ]);

    $this->actingAs($superAdmin)
        ->put(route('super-admin.subscription-plans.update', $plan), [
            'name' => 'Starter Plus',
            'slug' => 'starter-plus',
            'description' => 'Updated starter plan.',
            'monthly_price' => 79,
            'annual_price' => 790,
            'employee_limit' => 15,
            'client_limit' => 30,
            'project_limit' => 30,
            'storage_limit_mb' => 4096,
            'trial_days' => 14,
            'features' => "Projects\nReports",
            'status' => 'active',
            'display_order' => 1,
        ])
        ->assertRedirect(route('super-admin.subscription-plans.index'));

    $this->assertDatabaseHas('subscription_plans', ['id' => $plan->id, 'name' => 'Starter Plus', 'monthly_price' => 79]);
});

test('super admin can verify subscription payment', function () {
    $superAdmin = makeUser(['email' => 'super@elevanix.test', 'username' => 'super']);
    $company = makeCompany();
    $plan = SubscriptionPlan::create([
        'name' => 'Starter',
        'slug' => 'starter',
        'monthly_price' => 49,
        'annual_price' => 499,
        'employee_limit' => 10,
        'client_limit' => 25,
        'project_limit' => 20,
        'storage_limit_mb' => 2048,
        'trial_days' => 14,
        'status' => 'active',
        'display_order' => 1,
    ]);
    $subscription = Subscription::create([
        'company_id' => $company->id,
        'subscription_plan_id' => $plan->id,
        'status' => 'active',
        'starts_at' => now()->toDateString(),
        'renews_at' => now()->addMonth()->toDateString(),
        'monthly_price' => 49,
    ]);
    $payment = Payment::create([
        'company_id' => $company->id,
        'subscription_id' => $subscription->id,
        'subscription_plan_id' => $plan->id,
        'transaction_reference' => 'SUB-TEST-001',
        'payment_type' => 'subscription',
        'amount' => 49,
        'method' => 'bank_transfer',
        'status' => 'submitted',
    ]);

    $this->actingAs($superAdmin)
        ->post(route('super-admin.payments.status', [$payment, 'verified']), ['verification_note' => 'Bank receipt matched.'])
        ->assertRedirect();

    $this->assertDatabaseHas('payments', [
        'id' => $payment->id,
        'status' => 'verified',
        'verified_by' => $superAdmin->id,
        'verification_note' => 'Bank receipt matched.',
    ]);

    $this->assertDatabaseHas('subscriptions', [
        'id' => $subscription->id,
        'status' => 'active',
    ]);
});

test('verified payment cannot be verified twice', function () {
    $superAdmin = makeUser(['email' => 'payment-repeat-admin@example.test', 'username' => 'paymentrepeatadmin']);
    $company = makeCompany();
    $payment = Payment::create([
        'company_id' => $company->id,
        'transaction_reference' => 'SUB-TEST-REPEAT',
        'payment_type' => 'subscription',
        'amount' => 49,
        'method' => 'bank_transfer',
        'status' => 'verified',
    ]);

    $this->actingAs($superAdmin)
        ->post(route('super-admin.payments.status', [$payment, 'verified']), ['verification_note' => 'Duplicate check.'])
        ->assertSessionHas('error');
});

test('super admin can view report before download', function () {
    $superAdmin = makeUser(['email' => 'report-admin@example.test', 'username' => 'reportadmin']);
    makeCompany(['name' => 'Report Company', 'email' => 'report-company@example.test']);

    $this->actingAs($superAdmin)
        ->get(route('super-admin.reports.show', 'companies'))
        ->assertOk()
        ->assertSee('Company Registration Report')
        ->assertSee('Report Company')
        ->assertSee('Download CSV')
        ->assertSee('Download PDF');
});

test('super admin report links ignore stale report query values', function () {
    $superAdmin = makeUser(['email' => 'report-link-admin@example.test', 'username' => 'reportlinkadmin']);

    $this->actingAs($superAdmin)
        ->get(route('super-admin.reports.index', ['report' => 'missing-report', 'search' => 'Visible']))
        ->assertOk()
        ->assertSee(route('super-admin.reports.show', ['report' => 'companies', 'search' => 'Visible']))
        ->assertSee(route('super-admin.reports.export', ['report' => 'companies', 'search' => 'Visible']))
        ->assertSee(route('super-admin.reports.pdf', ['report' => 'companies', 'search' => 'Visible']))
        ->assertDontSee('/super-admin/reports/missing-report', false);
});

test('super admin can download report pdf', function () {
    $superAdmin = makeUser(['email' => 'pdf-admin@example.test', 'username' => 'pdfadmin']);
    makeCompany(['name' => 'PDF Company', 'email' => 'pdf-company@example.test']);

    $response = $this->actingAs($superAdmin)
        ->get(route('super-admin.reports.pdf', 'companies'));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

test('super admin csv report applies search filters', function () {
    $superAdmin = makeUser(['email' => 'filtered-report-admin@example.test', 'username' => 'filteredreportadmin']);
    makeCompany(['name' => 'Visible Export Company', 'email' => 'visible-export@example.test']);
    makeCompany(['name' => 'Hidden Export Company', 'email' => 'hidden-export@example.test']);

    $response = $this->actingAs($superAdmin)
        ->get(route('super-admin.reports.export', ['report' => 'companies', 'search' => 'Visible Export']));

    $response->assertOk();
    expect($response->streamedContent())
        ->toContain('Visible Export Company')
        ->not->toContain('Hidden Export Company');
});

test('company admin dashboard requires active subscription', function () {
    $company = makeCompany(['status' => 'active']);
    $user = makeUser([
        'company_id' => $company->id,
        'email' => 'company-admin@example.test',
        'username' => 'companyadmin',
        'role' => 'company_admin',
    ]);

    $this->actingAs($user)
        ->get(route('company-admin.dashboard'))
        ->assertForbidden();
});

test('company scoped queries only return current company records', function () {
    $company = makeCompany(['name' => 'Tenant One']);
    $otherCompany = makeCompany(['name' => 'Tenant Two']);
    $admin = makeUser(['company_id' => $company->id, 'role' => 'company_admin']);

    $visibleClient = Client::create([
        'company_id' => $company->id,
        'name' => 'Visible Client',
        'email' => 'visible@example.test',
    ]);
    Client::create([
        'company_id' => $otherCompany->id,
        'name' => 'Hidden Client',
        'email' => 'hidden@example.test',
    ]);

    expect(Client::query()->forUserCompany($admin)->pluck('id')->all())->toBe([$visibleClient->id]);
});

test('company admin cannot access another company project', function () {
    $company = makeCompany(['name' => 'Tenant One']);
    $otherCompany = makeCompany(['name' => 'Tenant Two']);
    $admin = makeUser(['company_id' => $company->id, 'role' => 'company_admin']);
    $otherProject = Project::create([
        'company_id' => $otherCompany->id,
        'name' => 'Private Project',
        'status' => 'in_progress',
    ]);

    expect(Gate::forUser($admin)->allows('view', $otherProject))->toBeFalse();
    expect(Gate::forUser($admin)->allows('update', $otherProject))->toBeFalse();
});

test('employee cannot view another company task', function () {
    $company = makeCompany(['name' => 'Tenant One']);
    $otherCompany = makeCompany(['name' => 'Tenant Two']);
    $employee = makeUser(['company_id' => $company->id, 'role' => 'employee']);
    $otherEmployee = makeUser(['company_id' => $otherCompany->id, 'role' => 'employee']);
    $otherProject = Project::create([
        'company_id' => $otherCompany->id,
        'name' => 'External Project',
        'status' => 'in_progress',
    ]);
    $task = Task::create([
        'company_id' => $otherCompany->id,
        'project_id' => $otherProject->id,
        'assignee_id' => $otherEmployee->id,
        'created_by' => $otherEmployee->id,
        'title' => 'External Task',
        'status' => 'todo',
    ]);

    expect(Gate::forUser($employee)->allows('view', $task))->toBeFalse();
    expect(Gate::forUser($employee)->allows('update', $task))->toBeFalse();
});

test('employee can view assigned project and task inside own company', function () {
    $company = makeCompany();
    $admin = makeUser(['company_id' => $company->id, 'role' => 'company_admin']);
    $employee = makeUser(['company_id' => $company->id, 'role' => 'employee']);
    $project = Project::create([
        'company_id' => $company->id,
        'manager_id' => $admin->id,
        'name' => 'Internal Project',
        'status' => 'in_progress',
    ]);
    $project->users()->attach($employee->id);
    $task = Task::create([
        'company_id' => $company->id,
        'project_id' => $project->id,
        'assignee_id' => $employee->id,
        'created_by' => $admin->id,
        'title' => 'Assigned Task',
        'status' => 'todo',
    ]);

    expect(Gate::forUser($employee)->allows('view', $project))->toBeTrue();
    expect(Gate::forUser($employee)->allows('view', $task))->toBeTrue();
});

test('work timer prevents duplicate active sessions', function () {
    $company = makeCompany();
    $employee = makeUser(['company_id' => $company->id, 'role' => 'employee']);

    app(WorkTimerService::class)->start($employee);

    app(WorkTimerService::class)->start($employee);
})->throws(ValidationException::class, 'Stop the current timer before starting a new one.');

test('work timer stop stores duration in minutes', function () {
    $company = makeCompany();
    $employee = makeUser(['company_id' => $company->id, 'role' => 'employee']);
    $service = app(WorkTimerService::class);

    $this->travelTo(now()->setTime(9, 0));
    $session = $service->start($employee);

    $this->travelTo(now()->setTime(10, 15));
    $stopped = $service->stop($employee, $session, 'Completed dashboard work.');

    expect($stopped->ended_at)->not->toBeNull();
    expect($stopped->duration_minutes)->toBe(75);
    expect($stopped->notes)->toBe('Completed dashboard work.');
});

test('system settings store safe typed platform defaults', function () {
    SystemSetting::put('registration_enabled', true, 'boolean');

    expect(SystemSetting::getValue('registration_enabled'))->toBeTrue();
    expect(SystemSetting::getValue('missing_setting', 'fallback'))->toBe('fallback');
});
