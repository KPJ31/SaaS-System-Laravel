<?php

use App\Models\Company;
use App\Models\CompanyEvent;
use App\Models\CompanySetting;
use App\Models\LeaveRequest;
use App\Models\PersonalTodo;
use App\Models\Project;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Task;
use App\Models\User;
use App\Notifications\CompanyEventNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

function phase9Company(array $attributes = []): Company
{
    return Company::create(array_merge([
        'name' => fake()->company(),
        'slug' => fake()->unique()->slug(),
        'email' => fake()->unique()->companyEmail(),
        'status' => 'active',
        'timezone' => 'UTC',
    ], $attributes));
}

function phase9Plan(array $attributes = []): SubscriptionPlan
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

function phase9User(string $role = 'company_admin', ?Company $company = null, array $attributes = []): User
{
    $company ??= phase9Company();
    $plan = phase9Plan();
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
        'role' => $role,
        'status' => 'active',
        'password' => Hash::make('Password@123'),
    ], $attributes));
}

function phase9Task(User $employee, array $attributes = []): Task
{
    $project = Project::create([
        'company_id' => $employee->company_id,
        'name' => fake()->words(3, true),
        'status' => 'active',
        'priority' => 'medium',
        'start_date' => '2026-08-04',
        'due_date' => '2026-08-20',
    ]);
    $project->users()->attach($employee->id);

    return Task::create(array_merge([
        'company_id' => $employee->company_id,
        'project_id' => $project->id,
        'assignee_id' => $employee->id,
        'title' => fake()->sentence(3),
        'status' => 'in_progress',
        'priority' => 'medium',
        'task_type' => 'task',
        'due_date' => '2026-08-11',
    ], $attributes));
}

test('company admin can create update and cancel a company event with server controlled ownership', function () {
    Notification::fake();
    $admin = phase9User('company_admin');
    $employee = phase9User('employee', $admin->company);
    $foreign = phase9User('employee', phase9Company());

    $this->actingAs($admin)
        ->post(route('company-admin.company-events.store'), [
            'company_id' => $foreign->company_id,
            'created_by' => $foreign->id,
            'title' => 'Product planning',
            'event_type' => 'meeting',
            'start_at' => '2026-08-10 09:00:00',
            'end_at' => '2026-08-10 10:00:00',
            'location' => 'Board room',
        ])
        ->assertRedirect();

    $event = CompanyEvent::firstOrFail();
    expect($event->company_id)->toBe($admin->company_id)
        ->and($event->created_by)->toBe($admin->id);
    Notification::assertSentTo($employee, CompanyEventNotification::class);
    Notification::assertNotSentTo($admin, CompanyEventNotification::class);
    Notification::assertNotSentTo($foreign, CompanyEventNotification::class);

    $this->actingAs($admin)
        ->put(route('company-admin.company-events.update', $event), [
            'title' => 'Product planning updated',
            'event_type' => 'workshop',
            'start_at' => '2026-08-10 11:00:00',
            'end_at' => '2026-08-10 12:00:00',
        ])
        ->assertRedirect();

    expect($event->fresh()->event_type)->toBe('workshop');

    $this->actingAs($admin)->post(route('company-admin.company-events.cancel', $event))->assertRedirect();
    expect($event->fresh()->status)->toBe('cancelled');
});

test('company event date validation and tenant isolation are enforced', function () {
    $admin = phase9User('company_admin');
    $foreignAdmin = phase9User('company_admin', phase9Company());
    $event = CompanyEvent::create([
        'company_id' => $foreignAdmin->company_id,
        'created_by' => $foreignAdmin->id,
        'title' => 'Hidden event',
        'event_type' => 'meeting',
        'start_at' => '2026-08-10 09:00:00',
        'status' => 'scheduled',
    ]);

    $this->actingAs($admin)
        ->post(route('company-admin.company-events.store'), [
            'title' => 'Invalid event',
            'event_type' => 'meeting',
            'start_at' => '2026-08-10 12:00:00',
            'end_at' => '2026-08-10 11:00:00',
        ])
        ->assertSessionHasErrors('end_at');

    $this->actingAs($admin)->get(route('company-admin.company-events.show', $event))->assertForbidden();
});

test('employee cannot manage company events', function () {
    $employee = phase9User('employee');

    $this->actingAs($employee)->get(route('company-admin.company-events.create'))->assertForbidden();
});

test('company admin calendar aggregates visible range without exposing employee personal todos', function () {
    $admin = phase9User('company_admin');
    $employee = phase9User('employee', $admin->company);
    $foreignEmployee = phase9User('employee', phase9Company());
    $task = phase9Task($employee, ['title' => 'Visible task deadline', 'due_date' => '2026-08-11']);

    CompanyEvent::create(['company_id' => $admin->company_id, 'created_by' => $admin->id, 'title' => 'Visible company event', 'event_type' => 'meeting', 'start_at' => '2026-08-10 09:00:00', 'status' => 'scheduled']);
    Project::whereKey($task->project_id)->update(['name' => 'Visible project', 'due_date' => '2026-08-12']);
    LeaveRequest::create(['company_id' => $admin->company_id, 'user_id' => $employee->id, 'leave_type' => 'annual', 'start_date' => '2026-08-13', 'end_date' => '2026-08-13', 'total_days' => 1, 'reason' => 'Private reason', 'status' => 'approved']);
    PersonalTodo::create(['company_id' => $admin->company_id, 'user_id' => $employee->id, 'title' => 'Hidden employee todo', 'priority' => 'medium', 'status' => 'open', 'due_date' => '2026-08-14']);
    PersonalTodo::create(['company_id' => $admin->company_id, 'user_id' => $admin->id, 'title' => 'Visible admin todo', 'priority' => 'medium', 'status' => 'open', 'due_date' => '2026-08-14']);
    CompanyEvent::create(['company_id' => $foreignEmployee->company_id, 'created_by' => $foreignEmployee->id, 'title' => 'Foreign event', 'event_type' => 'meeting', 'start_at' => '2026-08-10 09:00:00', 'status' => 'scheduled']);

    $response = $this->actingAs($admin)
        ->getJson(route('company-admin.calendar.events', ['start' => '2026-08-09', 'end' => '2026-08-15']));

    $response->assertOk();
    $json = json_encode($response->json('events'));
    expect($json)
        ->toContain('Visible company event')
        ->toContain('Visible project')
        ->toContain('Visible task deadline')
        ->toContain($employee->name.' on leave')
        ->toContain('Visible admin todo')
        ->not->toContain('Hidden employee todo')
        ->not->toContain('Foreign event')
        ->not->toContain('Private reason');
});

test('employee calendar sees only assigned work own leave and own todos', function () {
    $employee = phase9User('employee');
    $other = phase9User('employee', $employee->company);
    $task = phase9Task($employee, ['title' => 'Assigned task deadline', 'due_date' => '2026-08-11']);
    phase9Task($other, ['title' => 'Other employee task deadline', 'due_date' => '2026-08-11']);
    CompanyEvent::create(['company_id' => $employee->company_id, 'created_by' => $other->id, 'title' => 'Company town hall', 'event_type' => 'meeting', 'start_at' => '2026-08-10 09:00:00', 'status' => 'scheduled']);
    LeaveRequest::create(['company_id' => $employee->company_id, 'user_id' => $employee->id, 'leave_type' => 'sick', 'start_date' => '2026-08-12', 'end_date' => '2026-08-12', 'total_days' => 1, 'reason' => 'Private medical reason', 'status' => 'approved']);
    PersonalTodo::create(['company_id' => $employee->company_id, 'user_id' => $employee->id, 'title' => 'My reminder', 'priority' => 'high', 'status' => 'open', 'due_date' => '2026-08-13']);
    PersonalTodo::create(['company_id' => $employee->company_id, 'user_id' => $other->id, 'title' => 'Other reminder', 'priority' => 'high', 'status' => 'open', 'due_date' => '2026-08-13']);

    $response = $this->actingAs($employee)
        ->getJson(route('employee.calendar.events', ['start' => '2026-08-09', 'end' => '2026-08-15']));

    $response->assertOk();
    $json = json_encode($response->json('events'));
    expect($json)
        ->toContain('Company town hall')
        ->toContain('Assigned task deadline')
        ->toContain('Approved leave')
        ->toContain('My reminder')
        ->not->toContain('Other employee task deadline')
        ->not->toContain('Other reminder')
        ->not->toContain('Private medical reason');
});

test('calendar endpoint limits results to requested date range', function () {
    $admin = phase9User('company_admin');
    CompanyEvent::create(['company_id' => $admin->company_id, 'created_by' => $admin->id, 'title' => 'Inside range', 'event_type' => 'meeting', 'start_at' => '2026-08-10 09:00:00', 'status' => 'scheduled']);
    CompanyEvent::create(['company_id' => $admin->company_id, 'created_by' => $admin->id, 'title' => 'Outside range', 'event_type' => 'meeting', 'start_at' => '2026-09-10 09:00:00', 'status' => 'scheduled']);

    $response = $this->actingAs($admin)
        ->getJson(route('company-admin.calendar.events', ['start' => '2026-08-01', 'end' => '2026-08-31']));

    $json = json_encode($response->json('events'));
    expect($json)->toContain('Inside range')->not->toContain('Outside range');
});

test('notification ownership open read and mark all are scoped to authenticated user', function () {
    $employee = phase9User('employee');
    $other = phase9User('employee', $employee->company);
    $own = $employee->notifications()->create(['id' => (string) Str::uuid(), 'type' => 'Phase9Notice', 'data' => ['title' => 'Own notice', 'url' => route('employee.todos.index')]]);
    $foreign = $other->notifications()->create(['id' => (string) Str::uuid(), 'type' => 'Phase9Notice', 'data' => ['title' => 'Foreign notice', 'url' => route('employee.todos.index')]]);

    $this->actingAs($employee)->get(route('employee.notifications.open', $own))->assertRedirect(route('employee.todos.index'));
    expect($own->fresh()->read_at)->not->toBeNull();

    $this->actingAs($employee)->get(route('employee.notifications.open', $foreign))->assertForbidden();
    $this->actingAs($employee)->post(route('employee.notifications.read-all'))->assertRedirect();
    expect($foreign->fresh()->read_at)->toBeNull();
});

test('task due reminder command sends enabled non duplicate reminders only for active tasks', function () {
    Notification::swap(app(\Illuminate\Notifications\ChannelManager::class));
    $employee = phase9User('employee');
    CompanySetting::create(['company_id' => $employee->company_id, 'timezone' => 'UTC', 'currency' => 'USD', 'settings' => ['task_due_reminder' => true]]);
    $due = phase9Task($employee, ['title' => 'Reminder task', 'due_date' => '2026-08-08']);
    phase9Task($employee, ['title' => 'Completed ignored task', 'due_date' => '2026-08-08', 'status' => 'completed']);

    $this->artisan('notifications:task-due-reminders', ['--date' => '2026-08-07'])->expectsOutput('Task due reminders sent: 1')->assertExitCode(0);
    $this->artisan('notifications:task-due-reminders', ['--date' => '2026-08-07'])->assertExitCode(0);

    $reminders = $employee->notifications()
        ->where('type', App\Notifications\TaskDueReminderNotification::class)
        ->get()
        ->filter(fn ($notification) => (int) ($notification->data['task_id'] ?? 0) === (int) $due->id);

    expect($reminders->count())->toBe(1);
});
