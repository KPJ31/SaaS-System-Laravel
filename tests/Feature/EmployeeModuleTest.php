<?php

use App\Models\Company;
use App\Models\LeaveRequest;
use App\Models\Project;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkSession;
use Illuminate\Support\Facades\Hash;

function employeeTestCompany(array $attributes = []): Company
{
    return Company::create(array_merge([
        'name' => fake()->company(),
        'slug' => fake()->unique()->slug(),
        'email' => fake()->unique()->companyEmail(),
        'status' => 'active',
    ], $attributes));
}

function employeeTestPlan(array $attributes = []): SubscriptionPlan
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

function employeeTestUser(Company|array|null $company = null, array $attributes = []): User
{
    if (is_array($company)) {
        $attributes = $company;
        $company = null;
    }

    $company ??= employeeTestCompany();
    $plan = employeeTestPlan();
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
        'role' => 'employee',
        'status' => 'active',
        'password' => Hash::make('Password@123'),
    ], $attributes));
}

function employeeTestTask(User $employee, array $attributes = []): Task
{
    $project = Project::create([
        'company_id' => $employee->company_id,
        'name' => fake()->words(3, true),
        'status' => 'in_progress',
        'priority' => 'medium',
    ]);
    $project->users()->attach($employee->id);

    return Task::create(array_merge([
        'company_id' => $employee->company_id,
        'project_id' => $project->id,
        'assignee_id' => $employee->id,
        'created_by' => $employee->id,
        'title' => fake()->sentence(3),
        'priority' => 'medium',
        'status' => 'assigned',
        'progress' => 0,
        'task_type' => 'task',
    ], $attributes));
}

test('employee can login and access dashboard', function () {
    $employee = employeeTestUser(['email' => 'employee@example.test', 'username' => 'employee']);

    $this->post(route('login.store'), ['login' => 'employee@example.test', 'password' => 'Password@123'])
        ->assertRedirect(route('employee.dashboard'));

    $this->actingAs($employee)->get(route('employee.dashboard'))->assertOk()->assertSee('My Workspace');
});

test('employee cannot access admin areas', function () {
    $employee = employeeTestUser();

    $this->actingAs($employee)->get(route('super-admin.dashboard'))->assertForbidden();
    $this->actingAs($employee)->get(route('company-admin.dashboard'))->assertForbidden();
});

test('employee only sees own tasks', function () {
    $employee = employeeTestUser();
    employeeTestTask($employee, ['title' => 'Visible Task']);
    $other = User::factory()->create(['company_id' => $employee->company_id, 'role' => 'employee', 'status' => 'active']);
    employeeTestTask($other, ['title' => 'Hidden Task']);

    $this->actingAs($employee)->get(route('employee.tasks.index'))->assertOk()->assertSee('Visible Task')->assertDontSee('Hidden Task');
});

test('employee cannot see another company task', function () {
    $employee = employeeTestUser();
    $otherEmployee = employeeTestUser(employeeTestCompany());
    $task = employeeTestTask($otherEmployee, ['title' => 'External Task']);

    $this->actingAs($employee)->get(route('employee.tasks.show', $task))->assertForbidden();
});

test('employee starts and stops work timer', function () {
    $employee = employeeTestUser();
    $task = employeeTestTask($employee);

    $this->actingAs($employee)->post(route('employee.tasks.start', $task))->assertRedirect();
    $this->assertDatabaseHas('work_sessions', ['task_id' => $task->id, 'user_id' => $employee->id, 'status' => 'running']);

    $this->actingAs($employee)->post(route('employee.tasks.stop', $task), ['notes' => 'Finished today'])->assertRedirect();
    $this->assertDatabaseHas('work_sessions', ['task_id' => $task->id, 'user_id' => $employee->id, 'status' => 'stopped', 'notes' => 'Finished today']);
});

test('employee cannot start multiple timers', function () {
    $employee = employeeTestUser();
    $first = employeeTestTask($employee);
    $second = employeeTestTask($employee);

    $this->actingAs($employee)->post(route('employee.tasks.start', $first))->assertRedirect();
    $this->actingAs($employee)->post(route('employee.tasks.start', $second))->assertSessionHasErrors('timer');
});

test('employee submits task for review', function () {
    $employee = employeeTestUser();
    $task = employeeTestTask($employee, ['status' => 'in_progress', 'progress' => 75]);

    $this->actingAs($employee)->patch(route('employee.tasks.status', $task), ['status' => 'submitted'])->assertRedirect();
    $this->assertDatabaseHas('tasks', ['id' => $task->id, 'status' => 'submitted', 'progress' => 100]);
});

test('employee creates leave request', function () {
    $employee = employeeTestUser();

    $this->actingAs($employee)->post(route('employee.leave-requests.store'), [
        'leave_type' => 'annual',
        'start_date' => now()->addDays(2)->toDateString(),
        'end_date' => now()->addDays(3)->toDateString(),
        'reason' => 'Family event',
    ])->assertRedirect(route('employee.leave-requests.index'));

    $this->assertDatabaseHas('leave_requests', ['user_id' => $employee->id, 'status' => 'pending', 'total_days' => 2]);
});

test('employee views own performance', function () {
    $employee = employeeTestUser();
    employeeTestTask($employee, ['status' => 'completed', 'progress' => 100, 'completed_at' => now()]);
    WorkSession::create(['company_id' => $employee->company_id, 'user_id' => $employee->id, 'started_at' => now()->subHour(), 'ended_at' => now(), 'duration_minutes' => 60, 'status' => 'stopped']);

    $this->actingAs($employee)->get(route('employee.performance.index'))->assertOk()->assertSee('Performance');
});

test('suspended employee cannot access dashboard', function () {
    $employee = employeeTestUser(null, ['status' => 'suspended']);

    $this->actingAs($employee)->get(route('employee.dashboard'))->assertForbidden();
});
