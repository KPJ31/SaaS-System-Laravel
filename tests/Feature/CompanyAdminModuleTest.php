<?php

use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\LeaveRequest;
use App\Models\Payment;
use App\Models\Project;
use App\Models\ProjectRequest;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
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

test('suspended company admin session is blocked from company admin routes', function () {
    $admin = companyAdminTestAdmin(null, ['status' => 'suspended']);

    $this->actingAs($admin)
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

test('company admin cannot filter tasks by another company project', function () {
    $admin = companyAdminTestAdmin();
    $otherCompany = companyAdminTestCompany();
    $otherProject = Project::create([
        'company_id' => $otherCompany->id,
        'name' => 'Hidden Filter Project',
        'status' => 'active',
        'priority' => 'medium',
    ]);

    $this->actingAs($admin)
        ->get(route('company-admin.tasks.index', ['project_id' => $otherProject->id]))
        ->assertForbidden();
});

test('invalid task status transition is blocked', function () {
    $admin = companyAdminTestAdmin();
    $project = Project::create([
        'company_id' => $admin->company_id,
        'name' => 'Task Flow Project',
        'status' => 'active',
        'priority' => 'medium',
    ]);
    $task = \App\Models\Task::create([
        'company_id' => $admin->company_id,
        'project_id' => $project->id,
        'created_by' => $admin->id,
        'title' => 'Cannot jump directly',
        'status' => 'todo',
        'priority' => 'medium',
        'progress' => 0,
        'task_type' => 'task',
    ]);

    $this->actingAs($admin)
        ->post(route('company-admin.tasks.status', [$task, 'completed']))
        ->assertSessionHas('error');
});

test('project request conversion can happen only once', function () {
    $admin = companyAdminTestAdmin();
    $request = ProjectRequest::create([
        'company_id' => $admin->company_id,
        'title' => 'One Time Conversion',
        'description' => 'Convert once only.',
        'status' => 'approved',
    ]);

    $this->actingAs($admin)
        ->post(route('company-admin.project-requests.convert', $request))
        ->assertRedirect();

    $this->actingAs($admin)
        ->post(route('company-admin.project-requests.convert', $request->fresh()))
        ->assertStatus(422);
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

test('company admin cannot verify the same payment twice', function () {
    $admin = companyAdminTestAdmin();
    $payment = Payment::create([
        'company_id' => $admin->company_id,
        'payment_type' => 'client_project',
        'amount' => 250,
        'method' => 'bank_transfer',
        'status' => 'paid',
    ]);

    $this->actingAs($admin)
        ->post(route('company-admin.payments.verify', $payment), ['verification_note' => 'Duplicate receipt.'])
        ->assertSessionHas('error');
});

test('company admin payment amount must be positive', function () {
    $admin = companyAdminTestAdmin();

    $this->actingAs($admin)
        ->post(route('company-admin.payments.store'), [
            'amount' => 0,
            'method' => 'bank_transfer',
            'status' => 'requested',
        ])
        ->assertSessionHasErrors('amount');
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

test('invoice paid amount cannot exceed total', function () {
    $admin = companyAdminTestAdmin();
    $client = Client::create(['company_id' => $admin->company_id, 'name' => 'Invoice Limit Client']);

    $this->actingAs($admin)
        ->post(route('company-admin.invoices.store'), [
            'client_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'subtotal' => 100,
            'tax' => 0,
            'total' => 100,
            'paid_amount' => 150,
            'status' => 'draft',
        ])
        ->assertSessionHasErrors('paid_amount');
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

test('company admin csv report applies filters and stays company scoped', function () {
    $admin = companyAdminTestAdmin();
    $visible = User::factory()->create([
        'company_id' => $admin->company_id,
        'role' => 'employee',
        'status' => 'active',
        'name' => 'Visible Filtered Employee',
    ]);
    User::factory()->create([
        'company_id' => $admin->company_id,
        'role' => 'employee',
        'status' => 'suspended',
        'name' => 'Hidden Suspended Employee',
    ]);
    User::factory()->create([
        'company_id' => companyAdminTestCompany()->id,
        'role' => 'employee',
        'status' => 'active',
        'name' => 'Hidden Other Company Employee',
    ]);

    $response = $this->actingAs($admin)
        ->get(route('company-admin.reports.export', ['report' => 'employees', 'status' => 'active', 'search' => 'Visible']));

    $response->assertOk();
    expect($response->streamedContent())
        ->toContain($visible->name)
        ->not->toContain('Hidden Suspended Employee')
        ->not->toContain('Hidden Other Company Employee');
});

test('company admin csv report protects spreadsheet formulas', function () {
    $admin = companyAdminTestAdmin();
    User::factory()->create([
        'company_id' => $admin->company_id,
        'role' => 'employee',
        'status' => 'active',
        'name' => '=HYPERLINK("https://example.test")',
    ]);

    $response = $this->actingAs($admin)
        ->get(route('company-admin.reports.export', ['report' => 'employees']));

    $response->assertOk();
    expect($response->streamedContent())->toContain("'=HYPERLINK");
});

test('company admin report rejects another company employee filter', function () {
    $admin = companyAdminTestAdmin();
    $otherCompany = companyAdminTestCompany();
    $otherEmployee = User::factory()->create(['company_id' => $otherCompany->id, 'role' => 'employee', 'status' => 'active']);

    $this->actingAs($admin)
        ->get(route('company-admin.reports.show', ['report' => 'attendance', 'employee_id' => $otherEmployee->id]))
        ->assertForbidden();
});

test('employee report export requires report and module permissions', function () {
    $this->seed(\Database\Seeders\PermissionSeeder::class);

    $company = companyAdminTestCompany();
    companyAdminTestAdmin($company);
    $employee = User::factory()->create([
        'company_id' => $company->id,
        'role' => 'employee',
        'status' => 'active',
    ]);
    $employee->syncDirectPermissions(['reports.view', 'reports.export']);

    $this->actingAs($employee)
        ->get(route('employee.reports.export', ['report' => 'payments']))
        ->assertForbidden();

    $employee->syncDirectPermissions(['reports.view', 'reports.export', 'payments.view']);

    $this->actingAs($employee->fresh())
        ->get(route('employee.reports.export', ['report' => 'payments']))
        ->assertOk();
});

test('company admin can view only own company attendance', function () {
    $admin = companyAdminTestAdmin();
    $employee = User::factory()->create(['company_id' => $admin->company_id, 'role' => 'employee', 'status' => 'active', 'name' => 'Visible Attendance Employee']);
    $otherCompany = companyAdminTestCompany();
    $otherEmployee = User::factory()->create(['company_id' => $otherCompany->id, 'role' => 'employee', 'status' => 'active', 'name' => 'Hidden Attendance Employee']);

    Attendance::create(['company_id' => $admin->company_id, 'user_id' => $employee->id, 'attendance_date' => now()->toDateString(), 'status' => 'present']);
    Attendance::create(['company_id' => $otherCompany->id, 'user_id' => $otherEmployee->id, 'attendance_date' => now()->toDateString(), 'status' => 'present']);

    $this->actingAs($admin)
        ->get(route('company-admin.attendance.index'))
        ->assertOk()
        ->assertSee('Visible Attendance Employee')
        ->assertDontSee('Hidden Attendance Employee');
});

test('company admin can export filtered attendance pdf', function () {
    $admin = companyAdminTestAdmin();
    $employee = User::factory()->create(['company_id' => $admin->company_id, 'role' => 'employee', 'status' => 'active']);
    Attendance::create([
        'company_id' => $admin->company_id,
        'user_id' => $employee->id,
        'attendance_date' => now()->toDateString(),
        'status' => 'present',
    ]);

    $response = $this->actingAs($admin)
        ->get(route('company-admin.attendance.pdf', ['employee_id' => $employee->id]));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

test('company admin can update working hours settings', function () {
    $admin = companyAdminTestAdmin();

    $this->actingAs($admin)
        ->put(route('company-admin.settings.update'), [
            'timezone' => 'UTC',
            'currency' => 'USD',
            'default_project_status' => 'planning',
            'default_task_priority' => 'medium',
            'work_start_time' => '08:30',
            'work_end_time' => '17:00',
            'lunch_break_minutes' => 30,
            'late_grace_minutes' => 10,
            'early_check_in_allowance_minutes' => 30,
            'early_departure_grace_minutes' => 10,
            'full_day_minutes' => 480,
            'half_day_minutes' => 240,
            'working_days' => [1, 2, 3, 4, 5],
            'attendance_enabled' => 1,
            'auto_absence_enabled' => 1,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('company_settings', ['company_id' => $admin->company_id, 'timezone' => 'UTC']);
});

test('working hours settings require valid day targets', function () {
    $admin = companyAdminTestAdmin();

    $this->actingAs($admin)
        ->put(route('company-admin.settings.update'), [
            'timezone' => 'UTC',
            'currency' => 'USD',
            'default_project_status' => 'planning',
            'default_task_priority' => 'medium',
            'work_start_time' => '17:00',
            'work_end_time' => '08:30',
            'lunch_break_minutes' => 30,
            'late_grace_minutes' => 10,
            'early_check_in_allowance_minutes' => 30,
            'early_departure_grace_minutes' => 10,
            'full_day_minutes' => 240,
            'half_day_minutes' => 480,
            'working_days' => [],
        ])
        ->assertSessionHasErrors(['work_end_time', 'half_day_minutes', 'working_days']);
});

test('attendance correction creates audit log', function () {
    $admin = companyAdminTestAdmin();
    $employee = User::factory()->create(['company_id' => $admin->company_id, 'role' => 'employee', 'status' => 'active']);
    $attendance = Attendance::create(['company_id' => $admin->company_id, 'user_id' => $employee->id, 'attendance_date' => '2026-08-03', 'check_in_time' => '2026-08-03 09:00:00', 'status' => 'late']);

    $this->actingAs($admin)
        ->patch(route('company-admin.attendance.update', $attendance), [
            'check_in_time' => '2026-08-03 08:30:00',
            'check_out_time' => '2026-08-03 17:00:00',
            'correction_reason' => 'Corrected from signed attendance sheet.',
        ])
        ->assertRedirect();

    expect(AuditLog::where('action', 'attendance_corrected')->where('company_id', $admin->company_id)->exists())->toBeTrue();
});

test('automatic absence command avoids duplicates and respects leave', function () {
    $admin = companyAdminTestAdmin();
    $employee = User::factory()->create(['company_id' => $admin->company_id, 'role' => 'employee', 'status' => 'active']);
    $leaveEmployee = User::factory()->create(['company_id' => $admin->company_id, 'role' => 'employee', 'status' => 'active']);
    LeaveRequest::create(['company_id' => $admin->company_id, 'user_id' => $leaveEmployee->id, 'leave_type' => 'annual', 'start_date' => '2026-08-03', 'end_date' => '2026-08-03', 'total_days' => 1, 'reason' => 'Approved leave', 'status' => 'approved']);

    $this->artisan('attendance:mark-absent', ['--date' => '2026-08-03'])->assertExitCode(0);
    $this->artisan('attendance:mark-absent', ['--date' => '2026-08-03'])->assertExitCode(0);

    $this->assertDatabaseHas('attendances', ['company_id' => $admin->company_id, 'user_id' => $employee->id, 'status' => 'absent']);
    $this->assertDatabaseHas('attendances', ['company_id' => $admin->company_id, 'user_id' => $leaveEmployee->id, 'status' => 'on_leave']);
    expect(Attendance::where('company_id', $admin->company_id)->whereDate('attendance_date', '2026-08-03')->count())->toBe(2);
});
