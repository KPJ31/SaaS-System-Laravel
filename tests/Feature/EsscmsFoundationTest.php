<?php

use App\Models\Company;
use App\Models\CompanyRegistrationRequest;
use App\Models\User;
use App\Notifications\CompanyRegistrationApproved;
use App\Notifications\CompanyRegistrationRejected;
use App\Notifications\CompanyRegistrationReceived;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

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
        'email' => fake()->unique()->safeEmail(),
        'phone' => '+1 555 0100',
        'address' => '100 Test Street',
        'status' => 'active',
    ], $attributes));
}

test('landing page is available', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Run your software company with clarity and control.');
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

test('inactive user cannot login', function () {
    makeUser(['email' => 'inactive@example.test', 'username' => 'inactive', 'status' => 'inactive']);

    $this->post(route('login.store'), [
        'login' => 'inactive@example.test',
        'password' => 'Password@123',
    ])->assertSessionHasErrors('login');

    $this->assertGuest();
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
