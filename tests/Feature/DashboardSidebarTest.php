<?php

use App\Models\Company;
use App\Models\ProjectRequest;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

function sidebarTestCompany(array $attributes = []): Company
{
    return Company::create(array_merge([
        'name' => fake()->company(),
        'slug' => fake()->unique()->slug(),
        'email' => fake()->unique()->companyEmail(),
        'status' => 'active',
    ], $attributes));
}

function sidebarTestPlan(array $attributes = []): SubscriptionPlan
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

function sidebarTestUser(?Company $company = null, array $attributes = []): User
{
    $company ??= sidebarTestCompany();

    if (! $company->activeSubscription) {
        $plan = sidebarTestPlan();
        Subscription::create([
            'company_id' => $company->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->toDateString(),
            'renews_at' => now()->addMonth()->toDateString(),
            'monthly_price' => $plan->monthly_price,
        ]);
    }

    return User::factory()->create(array_merge([
        'company_id' => $company->id,
        'role' => 'employee',
        'status' => 'active',
        'password' => Hash::make('Password@123'),
    ], $attributes));
}

beforeEach(function (): void {
    $this->seed(\Database\Seeders\PermissionSeeder::class);
});

test('super admin sees only super admin sidebar', function () {
    $superAdmin = User::factory()->create(['name' => 'John Admin', 'role' => 'super_admin', 'status' => 'active']);

    $this->actingAs($superAdmin)
        ->get(route('super-admin.dashboard'))
        ->assertOk()
        ->assertSee('John Admin')
        ->assertSee('Super Admin')
        ->assertSee('sidebar-account-card', false)
        ->assertDontSee('sidebar-brand', false)
        ->assertSee('Platform')
        ->assertSee('Company Requests')
        ->assertSee('System Settings')
        ->assertDontSee('Employee Permissions')
        ->assertDontSee('My Work');
});

test('company admin sees only company admin sidebar', function () {
    $company = sidebarTestCompany();
    $admin = sidebarTestUser($company, ['role' => 'company_admin']);

    $this->actingAs($admin)
        ->get(route('company-admin.dashboard'))
        ->assertOk()
        ->assertSee($company->name)
        ->assertSee('Company Admin')
        ->assertSee('sidebar-account-card', false)
        ->assertDontSee('sidebar-brand', false)
        ->assertSee('Main')
        ->assertSee('Company')
        ->assertSee('People')
        ->assertSee('Employee Permissions')
        ->assertDontSee('Company Requests')
        ->assertDontSee('System Settings');
});

test('employee sees base employee sidebar', function () {
    $employee = sidebarTestUser();

    $this->actingAs($employee)
        ->get(route('employee.dashboard'))
        ->assertOk()
        ->assertSee($employee->company->name)
        ->assertSee('Employee')
        ->assertSee($employee->name)
        ->assertSee('sidebar-account-card', false)
        ->assertDontSee('Company Admin')
        ->assertDontSee('sidebar-brand', false)
        ->assertSee('My Work')
        ->assertSee('My Projects')
        ->assertSee('My Tasks')
        ->assertDontSee('Employee Permissions')
        ->assertDontSee('Company Requests');
});

test('employee sees and loses extra module after permission changes', function () {
    $employee = sidebarTestUser();

    $this->actingAs($employee)
        ->get(route('employee.dashboard'))
        ->assertOk()
        ->assertDontSee('Clients');

    $employee->syncDirectPermissions(['clients.view']);

    $this->actingAs($employee->fresh())
        ->get(route('employee.dashboard'))
        ->assertOk()
        ->assertSee('Company Operations')
        ->assertSee('Clients');

    $employee->fresh()->syncDirectPermissions([]);

    $this->actingAs($employee->fresh())
        ->get(route('employee.dashboard'))
        ->assertOk()
        ->assertDontSee('Clients');
});

test('employee never sees super admin modules', function () {
    $employee = sidebarTestUser();
    $employee->syncDirectPermissions(['clients.view', 'reports.view']);

    $this->actingAs($employee->fresh())
        ->get(route('employee.dashboard'))
        ->assertOk()
        ->assertDontSee('System Settings')
        ->assertDontSee('Platform Users')
        ->assertDontSee('Company Subscriptions');
});

test('active route highlights child and keeps parent submenu open', function () {
    $company = sidebarTestCompany();
    $admin = sidebarTestUser($company, ['role' => 'company_admin']);
    $employee = sidebarTestUser($company);

    $this->actingAs($admin)
        ->get(route('company-admin.employees.permissions.edit', $employee))
        ->assertOk()
        ->assertSee('sidebar-parent active', false)
        ->assertSee('sidebar-submenu show', false)
        ->assertSee('aria-current="page"', false);
});

test('zero badges are hidden', function () {
    $employee = sidebarTestUser();

    $this->actingAs($employee)
        ->get(route('employee.dashboard'))
        ->assertOk()
        ->assertDontSee('sidebar-badge', false);
});

test('company badge counts use only own company records', function () {
    $company = sidebarTestCompany();
    $otherCompany = sidebarTestCompany();
    $admin = sidebarTestUser($company, ['role' => 'company_admin']);

    ProjectRequest::create(['company_id' => $company->id, 'title' => 'Own Pending', 'status' => 'pending']);
    ProjectRequest::create(['company_id' => $otherCompany->id, 'title' => 'Other Pending', 'status' => 'pending']);

    $this->actingAs($admin)
        ->get(route('company-admin.dashboard'))
        ->assertOk()
        ->assertSee('<small class="sidebar-badge">1</small>', false)
        ->assertDontSee('<small class="sidebar-badge">2</small>', false);
});

test('suspended employee cannot access sidebar routes', function () {
    $employee = sidebarTestUser(null, ['status' => 'suspended']);
    $employee->syncDirectPermissions(['clients.view']);

    $this->actingAs($employee->fresh())
        ->get(route('employee.clients.index'))
        ->assertForbidden();
});

test('unauthorized direct url is blocked without permission', function () {
    $employee = sidebarTestUser();

    $this->actingAs($employee)
        ->get(route('employee.clients.index'))
        ->assertForbidden();
});

test('mobile sidebar markup includes accessible controls', function () {
    $employee = sidebarTestUser();

    $this->actingAs($employee)
        ->get(route('employee.dashboard'))
        ->assertOk()
        ->assertSee('data-sidebar-toggle', false)
        ->assertSee('aria-controls="app-sidebar"', false)
        ->assertSee('data-sidebar-overlay', false)
        ->assertSee('aria-label="Close sidebar"', false);
});

test('sidebar removes public website and logout actions while topbar keeps logout', function () {
    $employee = sidebarTestUser();

    $this->actingAs($employee)
        ->get(route('employee.dashboard'))
        ->assertOk()
        ->assertDontSee('Visit Website')
        ->assertDontSee('sidebar-logout', false)
        ->assertDontSee('sidebar-footer', false)
        ->assertSee('action="'.route('logout').'"', false)
        ->assertSee('name="_token"', false);
});

test('topbar profile dropdown includes profile password and logout actions', function () {
    $employee = sidebarTestUser();

    $this->actingAs($employee)
        ->get(route('employee.dashboard'))
        ->assertOk()
        ->assertSee('My Profile')
        ->assertSee('Change Password')
        ->assertSee('Logout')
        ->assertSee('method="POST"', false)
        ->assertSee('name="_token"', false);
});

test('company admin sidebar account card handles long company names and missing logo', function () {
    $longName = 'NovaStack Software International Product Engineering Workspace';
    $company = sidebarTestCompany(['name' => $longName, 'logo_path' => null]);
    $admin = sidebarTestUser($company, ['role' => 'company_admin']);

    $this->actingAs($admin)
        ->get(route('company-admin.dashboard'))
        ->assertOk()
        ->assertSee('sidebar-account-name text-truncate', false)
        ->assertSee('title="'.$longName.'"', false)
        ->assertSee('>N</span>', false);
});

test('employee sidebar only shows own company context', function () {
    $company = sidebarTestCompany(['name' => 'Company A Workspace']);
    $otherCompany = sidebarTestCompany(['name' => 'Company B Workspace']);
    $employee = sidebarTestUser($company);
    sidebarTestUser($otherCompany);

    $this->actingAs($employee)
        ->get(route('employee.dashboard'))
        ->assertOk()
        ->assertSee('Company A Workspace')
        ->assertDontSee('Company B Workspace');
});
