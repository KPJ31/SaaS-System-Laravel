<?php

use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Support\Facades\Hash;

function permissionTestCompany(array $attributes = []): Company
{
    return Company::create(array_merge([
        'name' => fake()->company(),
        'slug' => fake()->unique()->slug(),
        'email' => fake()->unique()->companyEmail(),
        'status' => 'active',
    ], $attributes));
}

function permissionTestPlan(array $attributes = []): SubscriptionPlan
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

function permissionTestUser(?Company $company = null, array $attributes = []): User
{
    $company ??= permissionTestCompany();

    if (! $company->activeSubscription) {
        $plan = permissionTestPlan();
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

function permissionTestAdmin(?Company $company = null): User
{
    return permissionTestUser($company, ['role' => 'company_admin']);
}

beforeEach(function (): void {
    $this->seed(\Database\Seeders\PermissionSeeder::class);
});

test('company admin can open permission page for own employee', function () {
    $company = permissionTestCompany();
    $admin = permissionTestAdmin($company);
    $employee = permissionTestUser($company);

    $this->actingAs($admin)
        ->get(route('company-admin.employees.permissions.edit', $employee))
        ->assertOk()
        ->assertSee('Manage '.$employee->name)
        ->assertSee('clients.view');
});

test('company admin cannot open permission page for another company employee', function () {
    $admin = permissionTestAdmin();
    $employee = permissionTestUser(permissionTestCompany());

    $this->actingAs($admin)
        ->get(route('company-admin.employees.permissions.edit', $employee))
        ->assertForbidden();
});

test('company admin can assign and remove employee permission', function () {
    $company = permissionTestCompany();
    $admin = permissionTestAdmin($company);
    $employee = permissionTestUser($company);

    $this->actingAs($admin)
        ->put(route('company-admin.employees.permissions.update', $employee), [
            'permissions' => ['clients.view', 'reports.view'],
        ])
        ->assertRedirect();

    expect($employee->fresh()->can('clients.view'))->toBeTrue();

    $this->actingAs($admin)
        ->put(route('company-admin.employees.permissions.update', $employee), [
            'permissions' => ['reports.view'],
        ])
        ->assertRedirect();

    $employee = $employee->fresh();
    expect($employee->can('clients.view'))->toBeFalse();
    expect($employee->can('reports.view'))->toBeTrue();
});

test('employee can access module after permission assignment and receives forbidden without permission', function () {
    $company = permissionTestCompany();
    $employee = permissionTestUser($company);
    Client::create(['company_id' => $company->id, 'name' => 'Allowed Client']);

    $this->actingAs($employee)
        ->get(route('employee.clients.index'))
        ->assertForbidden();

    $employee->syncDirectPermissions(['clients.view']);

    $this->actingAs($employee->fresh())
        ->get(route('employee.clients.index'))
        ->assertOk()
        ->assertSee('Allowed Client');
});

test('employee cannot access another company data through assigned permission', function () {
    $employee = permissionTestUser();
    $employee->syncDirectPermissions(['clients.view']);
    $otherClient = Client::create(['company_id' => permissionTestCompany()->id, 'name' => 'Hidden Client']);

    $this->actingAs($employee->fresh())
        ->get(route('employee.clients.show', $otherClient))
        ->assertForbidden();
});

test('company admin cannot assign platform permission', function () {
    $company = permissionTestCompany();
    $admin = permissionTestAdmin($company);
    $employee = permissionTestUser($company);

    $this->actingAs($admin)
        ->put(route('company-admin.employees.permissions.update', $employee), [
            'permissions' => ['clients.view', 'super-admin.dashboard'],
        ])
        ->assertSessionHasErrors('permissions.1');

    $employee = $employee->fresh();
    expect($employee->can('clients.view'))->toBeFalse();
    expect($employee->hasDirectPermission('super-admin.dashboard'))->toBeFalse();
});

test('reset permissions keeps employee role and basic access', function () {
    $company = permissionTestCompany();
    $admin = permissionTestAdmin($company);
    $employee = permissionTestUser($company);
    $employee->syncDirectPermissions(['clients.view']);

    $this->actingAs($admin)
        ->post(route('company-admin.employees.permissions.reset', $employee))
        ->assertRedirect();

    $employee = $employee->fresh();
    expect($employee->role)->toBe('employee');
    expect($employee->can('clients.view'))->toBeFalse();
    expect($employee->can('employee.dashboard'))->toBeTrue();
});

test('copy permissions works only inside same company', function () {
    $company = permissionTestCompany();
    $admin = permissionTestAdmin($company);
    $source = permissionTestUser($company);
    $target = permissionTestUser($company);
    $external = permissionTestUser(permissionTestCompany());
    $source->syncDirectPermissions(['clients.view', 'reports.view']);

    $this->actingAs($admin)
        ->post(route('company-admin.employees.permissions.copy', $target), ['source_employee_id' => $source->id])
        ->assertRedirect();

    expect($target->fresh()->can('reports.view'))->toBeTrue();

    $this->actingAs($admin)
        ->post(route('company-admin.employees.permissions.copy', $target), ['source_employee_id' => $external->id])
        ->assertForbidden();
});

test('suspended employee cannot use assigned permissions', function () {
    $employee = permissionTestUser(null, ['status' => 'suspended']);
    $employee->syncDirectPermissions(['clients.view']);

    expect($employee->fresh()->can('clients.view'))->toBeFalse();

    $this->actingAs($employee->fresh())
        ->get(route('employee.clients.index'))
        ->assertForbidden();
});

test('permission checkbox state displays and changes create audit log', function () {
    $company = permissionTestCompany();
    $admin = permissionTestAdmin($company);
    $employee = permissionTestUser($company);
    $employee->syncDirectPermissions(['clients.view']);

    $this->actingAs($admin)
        ->get(route('company-admin.employees.permissions.edit', $employee))
        ->assertOk()
        ->assertSee('value="clients.view" checked', false);

    $this->actingAs($admin)
        ->put(route('company-admin.employees.permissions.update', $employee), [
            'permissions' => ['reports.view'],
        ])
        ->assertRedirect();

    expect(AuditLog::where('company_id', $company->id)->where('module', 'employee-permissions')->exists())->toBeTrue();
});

test('permission summary page lists employee access totals', function () {
    $company = permissionTestCompany();
    $admin = permissionTestAdmin($company);
    $employee = permissionTestUser($company, ['name' => 'Permission Tester']);
    $employee->syncDirectPermissions(['clients.view']);

    $this->actingAs($admin)
        ->get(route('company-admin.employees.permissions.index'))
        ->assertOk()
        ->assertSee('Permission Tester')
        ->assertSee('clients');
});

test('permission catalog contains no duplicate assignable names', function () {
    $names = PermissionCatalog::assignableNames();

    expect($names)->toHaveCount(count(array_unique($names)));
    expect(Permission::whereIn('name', $names)->count())->toBe(count($names));
});
