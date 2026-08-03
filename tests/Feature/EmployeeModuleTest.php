<?php

use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\LeaveRequest;
use App\Models\Project;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkFile;
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

test('employee task project filter must belong to own assigned work', function () {
    $employee = employeeTestUser();
    $other = User::factory()->create(['company_id' => $employee->company_id, 'role' => 'employee', 'status' => 'active']);
    $otherTask = employeeTestTask($other);

    $this->actingAs($employee)
        ->get(route('employee.tasks.index', ['project_id' => $otherTask->project_id]))
        ->assertForbidden();
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
    expect(WorkSession::where('task_id', $task->id)->first()->ended_at)->not->toBeNull();
});

test('employee cannot start timer for unassigned task', function () {
    $employee = employeeTestUser();
    $other = User::factory()->create(['company_id' => $employee->company_id, 'role' => 'employee', 'status' => 'active']);
    $task = employeeTestTask($other);

    $this->actingAs($employee)->post(route('employee.tasks.start', $task))->assertForbidden();
});

test('employee cannot stop another employees session', function () {
    $employee = employeeTestUser();
    $other = User::factory()->create(['company_id' => $employee->company_id, 'role' => 'employee', 'status' => 'active']);
    $task = employeeTestTask($employee);
    WorkSession::create(['company_id' => $employee->company_id, 'user_id' => $other->id, 'task_id' => $task->id, 'started_at' => now()->subHour(), 'status' => 'running']);

    $this->actingAs($employee)->post(route('employee.tasks.stop', $task))->assertSessionHasErrors('timer');
});

test('employee cannot start multiple timers', function () {
    $employee = employeeTestUser();
    $first = employeeTestTask($employee);
    $second = employeeTestTask($employee);

    $this->actingAs($employee)->post(route('employee.tasks.start', $first))->assertRedirect();
    $this->actingAs($employee)->post(route('employee.tasks.start', $second))->assertSessionHasErrors('timer');
});

test('completed task cannot start timer', function () {
    $employee = employeeTestUser();
    $task = employeeTestTask($employee, ['status' => 'completed']);

    $this->actingAs($employee)->post(route('employee.tasks.start', $task))->assertSessionHasErrors('task_id');
});

test('timer remains visible after page refresh', function () {
    $employee = employeeTestUser();
    $task = employeeTestTask($employee);

    $this->actingAs($employee)->post(route('employee.tasks.start', $task))->assertRedirect();
    $this->actingAs($employee)->get(route('employee.tasks.show', $task))->assertOk()->assertSee('Timer running');
});

test('employee cannot download missing task file', function () {
    $employee = employeeTestUser();
    $task = employeeTestTask($employee);
    $file = WorkFile::create([
        'company_id' => $employee->company_id,
        'project_id' => $task->project_id,
        'task_id' => $task->id,
        'uploaded_by' => $employee->id,
        'original_name' => 'missing.pdf',
        'path' => 'work-files/'.$employee->company_id.'/missing.pdf',
        'mime_type' => 'application/pdf',
        'size' => 10,
    ]);

    $this->actingAs($employee)
        ->get(route('employee.files.download', $file))
        ->assertNotFound();
});

test('employee document filters must belong to own assigned work', function () {
    $employee = employeeTestUser();
    $other = User::factory()->create(['company_id' => $employee->company_id, 'role' => 'employee', 'status' => 'active']);
    $otherTask = employeeTestTask($other);

    $this->actingAs($employee)
        ->get(route('employee.documents.index', ['task_id' => $otherTask->id]))
        ->assertForbidden();
});

test('employee can check in and cannot check in twice', function () {
    $employee = employeeTestUser();

    $this->travelTo(now()->next('Monday')->setTime(8, 25));
    $this->actingAs($employee)->post(route('employee.attendance.check-in'))->assertRedirect();
    $this->assertDatabaseHas('attendances', ['user_id' => $employee->id, 'status' => 'present', 'is_late' => false]);

    $this->actingAs($employee)->post(route('employee.attendance.check-in'))->assertSessionHasErrors('attendance');
});

test('late check in respects grace period', function () {
    $employee = employeeTestUser();

    $this->travelTo(now()->next('Monday')->setTime(8, 38));
    $this->actingAs($employee)->post(route('employee.attendance.check-in'))->assertRedirect();
    expect(Attendance::where('user_id', $employee->id)->first()->is_late)->toBeFalse();

    $employee2 = User::factory()->create(['company_id' => $employee->company_id, 'role' => 'employee', 'status' => 'active']);
    $this->travelTo(now()->next('Tuesday')->setTime(8, 45));
    $this->actingAs($employee2)->post(route('employee.attendance.check-in'))->assertRedirect();
    $attendance = Attendance::where('user_id', $employee2->id)->first();
    expect($attendance->is_late)->toBeTrue()->and($attendance->late_minutes)->toBe(15);
});

test('employee can check out with lunch deduction and present status', function () {
    $employee = employeeTestUser();
    $date = now()->next('Monday')->toDateString();

    $this->travelTo($date.' 08:30:00');
    $this->actingAs($employee)->post(route('employee.attendance.check-in'))->assertRedirect();
    $this->travelTo($date.' 17:00:00');
    $this->actingAs($employee)->post(route('employee.attendance.check-out'))->assertRedirect();

    $attendance = Attendance::where('user_id', $employee->id)->first();
    expect($attendance->gross_minutes)->toBe(510)
        ->and($attendance->lunch_break_minutes)->toBe(30)
        ->and($attendance->net_work_minutes)->toBe(480)
        ->and($attendance->status)->toBe('present');
});

test('employee checkout can calculate half day and early departure', function () {
    $employee = employeeTestUser();
    $date = now()->next('Monday')->toDateString();

    $this->travelTo($date.' 08:30:00');
    $this->actingAs($employee)->post(route('employee.attendance.check-in'))->assertRedirect();
    $this->travelTo($date.' 12:45:00');
    $this->actingAs($employee)->post(route('employee.attendance.check-out'))->assertRedirect();

    $attendance = Attendance::where('user_id', $employee->id)->first();
    expect($attendance->status)->toBe('half_day')
        ->and($attendance->is_early_departure)->toBeTrue();
});

test('employee cannot check out before checking in', function () {
    $employee = employeeTestUser();

    $this->actingAs($employee)->post(route('employee.attendance.check-out'))->assertSessionHasErrors('attendance');
});

test('approved leave prevents attendance check in', function () {
    $employee = employeeTestUser();
    $date = now()->next('Monday')->toDateString();
    LeaveRequest::create(['company_id' => $employee->company_id, 'user_id' => $employee->id, 'leave_type' => 'annual', 'start_date' => $date, 'end_date' => $date, 'total_days' => 1, 'reason' => 'Approved leave', 'status' => 'approved']);

    $this->travelTo($date.' 08:30:00');
    $this->actingAs($employee)->post(route('employee.attendance.check-in'))->assertSessionHasErrors('attendance');
});

test('weekend check in is blocked', function () {
    $employee = employeeTestUser();

    $this->travelTo(now()->next('Saturday')->setTime(8, 30));
    $this->actingAs($employee)->post(route('employee.attendance.check-in'))->assertSessionHasErrors('attendance');
});

test('suspended employee cannot check in', function () {
    $employee = employeeTestUser(null, ['status' => 'suspended']);

    $this->actingAs($employee)->post(route('employee.attendance.check-in'))->assertForbidden();
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

test('employee work session csv export applies filters and stays personal', function () {
    $employee = employeeTestUser();
    $other = User::factory()->create(['company_id' => $employee->company_id, 'role' => 'employee', 'status' => 'active']);
    $task = employeeTestTask($employee);

    WorkSession::create([
        'company_id' => $employee->company_id,
        'user_id' => $employee->id,
        'task_id' => $task->id,
        'started_at' => '2026-08-03 09:00:00',
        'ended_at' => '2026-08-03 10:00:00',
        'duration_minutes' => 60,
        'notes' => 'Visible export note',
        'status' => 'stopped',
    ]);
    WorkSession::create([
        'company_id' => $employee->company_id,
        'user_id' => $other->id,
        'started_at' => '2026-08-03 11:00:00',
        'ended_at' => '2026-08-03 12:00:00',
        'duration_minutes' => 60,
        'notes' => 'Hidden other employee note',
        'status' => 'stopped',
    ]);

    $response = $this->actingAs($employee)
        ->get(route('employee.work-sessions.export', ['search' => 'Visible export']));

    $response->assertOk();
    expect($response->streamedContent())
        ->toContain('Visible export note')
        ->not->toContain('Hidden other employee note');
});

test('employee work session export protects spreadsheet formulas', function () {
    $employee = employeeTestUser();
    WorkSession::create([
        'company_id' => $employee->company_id,
        'user_id' => $employee->id,
        'started_at' => '2026-08-03 09:00:00',
        'ended_at' => '2026-08-03 10:00:00',
        'duration_minutes' => 60,
        'notes' => '=SUM(1,1)',
        'status' => 'stopped',
    ]);

    $response = $this->actingAs($employee)->get(route('employee.work-sessions.export'));

    $response->assertOk();
    expect($response->streamedContent())->toContain("'=SUM");
});

test('employee attendance export protects spreadsheet formulas', function () {
    $employee = employeeTestUser(['name' => '=Employee']);
    Attendance::create([
        'company_id' => $employee->company_id,
        'user_id' => $employee->id,
        'attendance_date' => '2026-08-03',
        'status' => 'present',
        'note' => '=CHECK',
    ]);

    $response = $this->actingAs($employee)->get(route('employee.attendance.export'));

    $response->assertOk();
    expect($response->streamedContent())
        ->toContain("'=Employee")
        ->toContain("'=CHECK");
});

test('employee activity search remains scoped to own activity', function () {
    $employee = employeeTestUser();
    $other = User::factory()->create(['company_id' => $employee->company_id, 'role' => 'employee', 'status' => 'active']);
    AuditLog::create([
        'company_id' => $employee->company_id,
        'user_id' => $employee->id,
        'action' => 'own_action',
        'description' => 'Visible own activity',
    ]);
    AuditLog::create([
        'company_id' => $employee->company_id,
        'user_id' => $other->id,
        'action' => 'needle_action',
        'description' => 'Hidden other employee activity',
    ]);

    $this->actingAs($employee)
        ->get(route('employee.activity.index', ['search' => 'needle_action']))
        ->assertOk()
        ->assertDontSee('Hidden other employee activity')
        ->assertDontSee('Visible own activity');
});

test('employee cancelling leave creates audit log', function () {
    $employee = employeeTestUser();
    $leave = LeaveRequest::create([
        'company_id' => $employee->company_id,
        'user_id' => $employee->id,
        'leave_type' => 'annual',
        'start_date' => now()->addDays(2)->toDateString(),
        'end_date' => now()->addDays(2)->toDateString(),
        'total_days' => 1,
        'reason' => 'Family event',
        'status' => 'pending',
    ]);

    $this->actingAs($employee)
        ->post(route('employee.leave-requests.cancel', $leave))
        ->assertRedirect();

    expect(AuditLog::where('company_id', $employee->company_id)->where('user_id', $employee->id)->where('action', 'leave_cancelled')->exists())->toBeTrue();
});

test('suspended employee cannot access dashboard', function () {
    $employee = employeeTestUser(null, ['status' => 'suspended']);

    $this->actingAs($employee)->get(route('employee.dashboard'))->assertForbidden();
});
