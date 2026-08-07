<?php

use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\LeaveRequest;
use App\Models\Payment;
use App\Models\PersonalTodo;
use App\Models\Project;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkFile;
use App\Models\WorkSession;
use App\Notifications\TaskNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

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

test('employee profile updates only allowed personal fields', function () {
    Storage::fake('public');
    Storage::disk('public')->put('profile-images/existing.png', 'avatar');
    $employee = employeeTestUser([
        'avatar' => 'profile-images/existing.png',
        'employee_code' => 'EMP-001',
        'job_title' => 'Developer',
        'department' => 'Engineering',
    ]);
    $originalCompanyId = $employee->company_id;
    $otherCompany = employeeTestCompany();

    $this->actingAs($employee)
        ->put(route('employee.profile.update'), [
            'name' => 'Updated Employee',
            'username' => 'updated-employee',
            'email' => 'updated-employee@example.test',
            'phone' => '555-3030',
            'address' => 'Updated address',
            'role' => 'company_admin',
            'company_id' => $otherCompany->id,
            'status' => 'suspended',
            'job_title' => 'Forged Manager',
            'department' => 'Forged Department',
            'employee_code' => 'FORGED',
            'remove_avatar' => '1',
        ])
        ->assertRedirect();

    $employee->refresh();

    expect($employee->name)->toBe('Updated Employee')
        ->and($employee->email)->toBe('updated-employee@example.test')
        ->and($employee->role)->toBe('employee')
        ->and($employee->company_id)->toBe($originalCompanyId)
        ->and($employee->status)->toBe('active')
        ->and($employee->job_title)->toBe('Developer')
        ->and($employee->department)->toBe('Engineering')
        ->and($employee->employee_code)->toBe('EMP-001')
        ->and($employee->avatar)->toBeNull();

    Storage::disk('public')->assertMissing('profile-images/existing.png');
});

test('employee password change requires current password and clears must change flag', function () {
    $employee = employeeTestUser(['must_change_password' => true]);
    $oldHash = $employee->password;

    $this->actingAs($employee)
        ->from(route('employee.password.edit'))
        ->put(route('employee.password.update'), [
            'current_password' => 'wrong-password',
            'password' => 'NewPassword@123',
            'password_confirmation' => 'NewPassword@123',
        ])
        ->assertRedirect(route('employee.password.edit'))
        ->assertSessionHasErrors('current_password');

    expect($employee->fresh()->password)->toBe($oldHash);

    $this->actingAs($employee)
        ->put(route('employee.password.update'), [
            'current_password' => 'Password@123',
            'password' => 'NewPassword@123',
            'password_confirmation' => 'NewPassword@123',
        ])
        ->assertRedirect();

    $employee->refresh();

    expect(Hash::check('NewPassword@123', $employee->password))->toBeTrue()
        ->and($employee->must_change_password)->toBeFalse();
});

test('employee cannot access admin areas', function () {
    $employee = employeeTestUser();

    $this->actingAs($employee)->get(route('super-admin.dashboard'))->assertForbidden();
    $this->actingAs($employee)->get(route('company-admin.dashboard'))->assertForbidden();
});

test('employee dashboard project stats ignore cross-company pivot assignments', function () {
    $employee = employeeTestUser();
    $otherCompany = employeeTestCompany();
    $externalProject = Project::create([
        'company_id' => $otherCompany->id,
        'name' => 'External Pivot Project',
        'status' => 'active',
        'priority' => 'medium',
    ]);

    $externalProject->users()->attach($employee->id);

    $this->actingAs($employee)
        ->get(route('employee.dashboard'))
        ->assertOk()
        ->assertSeeInOrder(['Projects', '<strong>0</strong>'], false)
        ->assertSeeInOrder(['Active Projects', '<strong>0</strong>'], false);
});

test('employee project list includes assigned projects and excludes unassigned projects', function () {
    $employee = employeeTestUser();
    $assignedProject = Project::create([
        'company_id' => $employee->company_id,
        'name' => 'Visible Assigned Project',
        'status' => 'active',
        'priority' => 'medium',
    ]);
    $assignedProject->users()->attach($employee->id);

    Project::create([
        'company_id' => $employee->company_id,
        'name' => 'Hidden Unassigned Project',
        'status' => 'active',
        'priority' => 'medium',
    ]);

    $this->actingAs($employee)
        ->get(route('employee.projects.index'))
        ->assertOk()
        ->assertSee('Visible Assigned Project')
        ->assertDontSee('Hidden Unassigned Project');
});

test('employee cannot view unassigned project in own company', function () {
    $employee = employeeTestUser();
    $project = Project::create([
        'company_id' => $employee->company_id,
        'name' => 'Hidden Own Company Project',
        'status' => 'active',
        'priority' => 'medium',
    ]);

    $this->actingAs($employee)
        ->get(route('employee.projects.show', $project))
        ->assertForbidden();
});

test('employee can view project through assigned task', function () {
    $employee = employeeTestUser();
    $task = employeeTestTask($employee, ['title' => 'Project Access Task']);

    $this->actingAs($employee)
        ->get(route('employee.projects.show', $task->project))
        ->assertOk()
        ->assertSee('Project Access Task');
});

test('employee project workspace only shows downloadable files', function () {
    $employee = employeeTestUser();
    $task = employeeTestTask($employee);
    $other = User::factory()->create(['company_id' => $employee->company_id, 'role' => 'employee', 'status' => 'active']);

    WorkFile::create([
        'company_id' => $employee->company_id,
        'project_id' => $task->project_id,
        'task_id' => $task->id,
        'uploaded_by' => $other->id,
        'original_name' => 'visible-task-file.pdf',
        'path' => 'work-files/'.$employee->company_id.'/visible-task-file.pdf',
        'mime_type' => 'application/pdf',
        'size' => 10,
    ]);
    WorkFile::create([
        'company_id' => $employee->company_id,
        'project_id' => $task->project_id,
        'uploaded_by' => $other->id,
        'original_name' => 'hidden-project-file.pdf',
        'path' => 'work-files/'.$employee->company_id.'/hidden-project-file.pdf',
        'mime_type' => 'application/pdf',
        'size' => 10,
    ]);

    $this->actingAs($employee)
        ->get(route('employee.projects.show', $task->project))
        ->assertOk()
        ->assertSee('visible-task-file.pdf')
        ->assertDontSee('hidden-project-file.pdf');
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

test('attendance service calculates late lunch and early departure consistently', function () {
    $settings = app(\App\Services\AttendanceService::class)->settingsForCompany(employeeTestUser()->company_id);
    $summary = app(\App\Services\AttendanceService::class)->calculateSummary(
        \Carbon\Carbon::parse('2026-08-03 08:45:00', $settings['timezone']),
        \Carbon\Carbon::parse('2026-08-03 16:30:00', $settings['timezone']),
        $settings
    );

    expect((int) $summary['gross_minutes'])->toBe(465)
        ->and((int) $summary['lunch_break_minutes'])->toBe(30)
        ->and((int) $summary['net_work_minutes'])->toBe(435)
        ->and($summary['status'])->toBe('half_day')
        ->and($summary['is_late'])->toBeTrue()
        ->and((int) $summary['late_minutes'])->toBe(15)
        ->and($summary['is_early_departure'])->toBeTrue();
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
    Notification::fake();
    $employee = employeeTestUser();
    $admin = User::factory()->create(['company_id' => $employee->company_id, 'role' => 'company_admin', 'status' => 'active']);
    $task = employeeTestTask($employee, ['status' => 'in_progress', 'progress' => 75]);

    $this->actingAs($employee)->patch(route('employee.tasks.status', $task), ['status' => 'submitted'])->assertRedirect();
    $this->assertDatabaseHas('tasks', ['id' => $task->id, 'status' => 'submitted', 'progress' => 100]);
    Notification::assertSentTo($admin, TaskNotification::class);
});

test('employee cannot complete task directly', function () {
    $employee = employeeTestUser();
    $task = employeeTestTask($employee, ['status' => 'submitted', 'progress' => 100]);

    $this->actingAs($employee)
        ->patch(route('employee.tasks.status', $task), ['status' => 'completed'])
        ->assertSessionHasErrors('status');

    expect($task->fresh()->status)->toBe('submitted');
});

test('employee valid task transitions follow workflow', function () {
    $employee = employeeTestUser();
    $task = employeeTestTask($employee, ['status' => 'assigned', 'progress' => 0]);

    $this->actingAs($employee)
        ->patch(route('employee.tasks.status', $task), ['status' => 'in_progress'])
        ->assertRedirect();
    expect($task->fresh()->status)->toBe('in_progress');

    $this->actingAs($employee)
        ->patch(route('employee.tasks.status', $task->fresh()), ['status' => 'blocked', 'blocked_reason' => 'Waiting for credentials.'])
        ->assertRedirect();
    expect($task->fresh()->status)->toBe('blocked');

    $this->actingAs($employee)
        ->patch(route('employee.tasks.status', $task->fresh()), ['status' => 'in_progress'])
        ->assertRedirect();
    expect($task->fresh()->status)->toBe('in_progress');
});

test('employee cannot comment on another employees task', function () {
    $employee = employeeTestUser();
    $other = User::factory()->create(['company_id' => $employee->company_id, 'role' => 'employee', 'status' => 'active']);
    $task = employeeTestTask($other);

    $this->actingAs($employee)
        ->post(route('employee.tasks.comments.store', $task), ['comment' => 'Not my task'])
        ->assertForbidden();
});

test('employee comment author is server controlled', function () {
    $employee = employeeTestUser();
    $task = employeeTestTask($employee);
    $other = User::factory()->create(['company_id' => $employee->company_id, 'role' => 'employee', 'status' => 'active']);

    $this->actingAs($employee)
        ->post(route('employee.tasks.comments.store', $task), [
            'comment' => 'Server-owned author',
            'user_id' => $other->id,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('task_comments', [
        'task_id' => $task->id,
        'user_id' => $employee->id,
        'comment' => 'Server-owned author',
    ]);
});

test('employee can manage own personal todo', function () {
    $employee = employeeTestUser();

    $this->actingAs($employee)
        ->post(route('employee.todos.store'), [
            'title' => 'Prepare daily notes',
            'priority' => 'high',
            'due_date' => now()->toDateString(),
            'pinned' => 1,
        ])
        ->assertRedirect();

    $todo = PersonalTodo::where('user_id', $employee->id)->firstOrFail();
    expect($todo->company_id)->toBe($employee->company_id)
        ->and($todo->pinned)->toBeTrue();

    $this->actingAs($employee)
        ->post(route('employee.todos.complete', $todo))
        ->assertRedirect();

    expect($todo->fresh()->status)->toBe('completed');
});

test('employee cannot access another users personal todo', function () {
    $employee = employeeTestUser();
    $other = User::factory()->create(['company_id' => $employee->company_id, 'role' => 'employee', 'status' => 'active']);
    $todo = PersonalTodo::create([
        'company_id' => $employee->company_id,
        'user_id' => $other->id,
        'title' => 'Other employee private todo',
        'priority' => 'medium',
        'status' => 'open',
    ]);

    $this->actingAs($employee)
        ->patch(route('employee.todos.update', $todo), [
            'title' => 'Forged todo update',
            'priority' => 'medium',
        ])
        ->assertForbidden();

    $this->actingAs($employee)
        ->get(route('employee.todos.index'))
        ->assertOk()
        ->assertDontSee('Other employee private todo');
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

    $this->actingAs($employee)->get(route('employee.performance.index'))->assertOk()->assertSee('My Work Summary');
});

test('employee personal work summary excludes colleague data', function () {
    $employee = employeeTestUser();
    $colleague = User::factory()->create(['company_id' => $employee->company_id, 'role' => 'employee', 'status' => 'active']);
    $project = Project::create(['company_id' => $employee->company_id, 'name' => 'Personal Summary Project', 'status' => 'active', 'priority' => 'medium']);

    employeeTestTask($employee, ['project_id' => $project->id, 'title' => 'Visible Personal Summary Task', 'status' => 'completed', 'progress' => 100, 'completed_at' => now()]);
    employeeTestTask($colleague, ['project_id' => $project->id, 'title' => 'Hidden Colleague Summary Task', 'status' => 'completed', 'progress' => 100, 'completed_at' => now()]);
    WorkSession::create(['company_id' => $employee->company_id, 'user_id' => $employee->id, 'project_id' => $project->id, 'started_at' => now()->subHours(2), 'ended_at' => now()->subHour(), 'duration_minutes' => 60, 'status' => 'stopped', 'notes' => 'Visible personal work note']);
    WorkSession::create(['company_id' => $employee->company_id, 'user_id' => $colleague->id, 'project_id' => $project->id, 'started_at' => now()->subHours(2), 'ended_at' => now()->subHour(), 'duration_minutes' => 60, 'status' => 'stopped', 'notes' => 'Hidden colleague work note']);

    $this->actingAs($employee)
        ->get(route('employee.performance.index'))
        ->assertOk()
        ->assertSee('Visible Personal Summary Task')
        ->assertSee('Visible personal work note')
        ->assertDontSee('Hidden Colleague Summary Task')
        ->assertDontSee('Hidden colleague work note');
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

test('employee can submit manual work log for assigned project task', function () {
    $employee = employeeTestUser();
    $task = employeeTestTask($employee);

    $this->travelTo('2026-08-04 18:00:00');

    $this->actingAs($employee)
        ->post(route('employee.work-sessions.store'), [
            'project_id' => $task->project_id,
            'task_id' => $task->id,
            'started_at' => '2026-08-04 09:00:00',
            'ended_at' => '2026-08-04 10:30:00',
            'notes' => 'Manual catch-up log',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('work_sessions', [
        'company_id' => $employee->company_id,
        'user_id' => $employee->id,
        'task_id' => $task->id,
        'duration_minutes' => 90,
        'is_manual' => true,
        'approval_status' => 'pending',
    ]);
});

test('manual work log rejects task from a different selected project', function () {
    $employee = employeeTestUser();
    $first = employeeTestTask($employee);
    $second = employeeTestTask($employee);

    $this->travelTo('2026-08-04 18:00:00');

    $this->actingAs($employee)
        ->post(route('employee.work-sessions.store'), [
            'project_id' => $first->project_id,
            'task_id' => $second->id,
            'started_at' => '2026-08-04 09:00:00',
            'ended_at' => '2026-08-04 10:30:00',
        ])
        ->assertSessionHasErrors('task_id');
});

test('manual work log cannot overlap existing session', function () {
    $employee = employeeTestUser();
    $task = employeeTestTask($employee);
    WorkSession::create([
        'company_id' => $employee->company_id,
        'user_id' => $employee->id,
        'project_id' => $task->project_id,
        'task_id' => $task->id,
        'started_at' => '2026-08-04 09:00:00',
        'ended_at' => '2026-08-04 10:00:00',
        'duration_minutes' => 60,
        'status' => 'stopped',
    ]);

    $this->travelTo('2026-08-04 18:00:00');

    $this->actingAs($employee)
        ->post(route('employee.work-sessions.store'), [
            'project_id' => $task->project_id,
            'task_id' => $task->id,
            'started_at' => '2026-08-04 09:30:00',
            'ended_at' => '2026-08-04 10:30:00',
        ])
        ->assertSessionHasErrors('started_at');
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

test('delegated employee dashboard payment stats exclude platform subscription payments', function () {
    $this->seed(\Database\Seeders\PermissionSeeder::class);

    $employee = employeeTestUser();
    $employee->syncDirectPermissions(['payments.view']);

    Payment::create([
        'company_id' => $employee->company_id,
        'transaction_reference' => 'CLIENT-PENDING',
        'payment_type' => 'client_project',
        'amount' => 250,
        'method' => 'bank_transfer',
        'status' => 'proof_submitted',
    ]);

    Payment::create([
        'company_id' => $employee->company_id,
        'transaction_reference' => 'CLIENT-PAID',
        'payment_type' => 'client_project',
        'amount' => 400,
        'method' => 'bank_transfer',
        'status' => 'paid',
    ]);

    Payment::create([
        'company_id' => $employee->company_id,
        'transaction_reference' => 'SUB-PENDING',
        'payment_type' => 'subscription',
        'amount' => 49,
        'method' => 'bank_transfer',
        'status' => 'submitted',
    ]);

    Payment::create([
        'company_id' => $employee->company_id,
        'transaction_reference' => 'SUB-PAID',
        'payment_type' => 'subscription',
        'amount' => 99,
        'method' => 'bank_transfer',
        'status' => 'verified',
    ]);

    $this->actingAs($employee->fresh())
        ->get(route('employee.dashboard'))
        ->assertOk()
        ->assertSeeInOrder(['Pending Payments', '<strong>1</strong>'], false)
        ->assertSeeInOrder(['Paid Payments', '<strong>1</strong>'], false);
});
