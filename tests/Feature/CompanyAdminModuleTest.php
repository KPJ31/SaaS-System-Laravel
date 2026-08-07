<?php

use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\LeaveRequest;
use App\Models\Payment;
use App\Models\PersonalTodo;
use App\Models\Project;
use App\Models\ProjectRequest;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkFile;
use App\Models\WorkSession;
use App\Notifications\LeaveRequestStatusNotification;
use App\Notifications\TaskNotification;
use App\Services\ProjectProgressService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

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

test('company admin dashboard counts only own company records', function () {
    $admin = companyAdminTestAdmin();
    $otherCompany = companyAdminTestCompany();

    User::factory()->create(['company_id' => $admin->company_id, 'role' => 'employee', 'status' => 'active']);
    User::factory()->create(['company_id' => $otherCompany->id, 'role' => 'employee', 'status' => 'active']);

    Client::create(['company_id' => $admin->company_id, 'name' => 'Own Dashboard Client']);
    Client::create(['company_id' => $otherCompany->id, 'name' => 'Other Dashboard Client']);

    $ownProject = Project::create(['company_id' => $admin->company_id, 'name' => 'Own Dashboard Project', 'status' => 'active', 'priority' => 'medium']);
    $otherProject = Project::create(['company_id' => $otherCompany->id, 'name' => 'Other Dashboard Project', 'status' => 'active', 'priority' => 'medium']);

    Task::create(['company_id' => $admin->company_id, 'project_id' => $ownProject->id, 'title' => 'Own Dashboard Task', 'status' => 'assigned', 'priority' => 'medium']);
    Task::create(['company_id' => $otherCompany->id, 'project_id' => $otherProject->id, 'title' => 'Other Dashboard Task', 'status' => 'assigned', 'priority' => 'medium']);

    $this->actingAs($admin)
        ->get(route('company-admin.dashboard'))
        ->assertOk()
        ->assertSeeInOrder(['Total Employees', '<strong>1</strong>'], false)
        ->assertSeeInOrder(['Total Clients', '<strong>1</strong>'], false)
        ->assertSeeInOrder(['Active Projects', '<strong>1</strong>'], false)
        ->assertSeeInOrder(['Open Tasks', '<strong>1</strong>'], false)
        ->assertDontSee('Other Dashboard Client')
        ->assertDontSee('Other Dashboard Project')
        ->assertDontSee('Other Dashboard Task');
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

test('company admin can update only editable company profile fields', function () {
    $admin = companyAdminTestAdmin();

    $this->actingAs($admin)
        ->put(route('company-admin.company-profile.update'), [
            'name' => 'Updated Company Name',
            'email' => 'updated-company@example.test',
            'phone' => '555-1000',
            'website' => 'https://example.test',
            'business_type' => 'Software',
            'address' => 'Main Street',
            'description' => 'Updated description',
            'timezone' => 'UTC',
            'date_format' => 'Y-m-d',
            'status' => 'suspended',
        ])
        ->assertRedirect(route('company-admin.company-profile.show'));

    $this->assertDatabaseHas('companies', [
        'id' => $admin->company_id,
        'name' => 'Updated Company Name',
        'status' => 'active',
    ]);
});

test('company admin personal profile ignores protected account payload and removes avatar safely', function () {
    Storage::fake('public');
    Storage::disk('public')->put('profile-images/existing.png', 'avatar');
    $admin = companyAdminTestAdmin(null, ['avatar' => 'profile-images/existing.png']);
    $originalCompanyId = $admin->company_id;
    $otherCompany = companyAdminTestCompany();

    $this->actingAs($admin)
        ->put(route('company-admin.profile.update'), [
            'name' => 'Updated Admin Name',
            'username' => 'updated-admin',
            'email' => 'updated-admin@example.test',
            'phone' => '555-2020',
            'role' => 'super_admin',
            'company_id' => $otherCompany->id,
            'status' => 'suspended',
            'remove_avatar' => '1',
        ])
        ->assertRedirect();

    $admin->refresh();

    expect($admin->name)->toBe('Updated Admin Name')
        ->and($admin->role)->toBe('company_admin')
        ->and($admin->company_id)->toBe($originalCompanyId)
        ->and($admin->status)->toBe('active')
        ->and($admin->avatar)->toBeNull();

    Storage::disk('public')->assertMissing('profile-images/existing.png');
});

test('company admin cannot view another company client', function () {
    $admin = companyAdminTestAdmin();
    $otherCompany = companyAdminTestCompany();
    $client = Client::create(['company_id' => $otherCompany->id, 'name' => 'Hidden Client']);

    $this->actingAs($admin)
        ->get(route('company-admin.clients.show', $client))
        ->assertForbidden();
});

test('company admin client creation ignores protected company payload', function () {
    $admin = companyAdminTestAdmin();
    $otherCompany = companyAdminTestCompany();

    $this->actingAs($admin)
        ->post(route('company-admin.clients.store'), [
            'company_id' => $otherCompany->id,
            'name' => 'Forged Client',
            'email' => 'forged-client@example.test',
            'status' => 'active',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('clients', [
        'company_id' => $admin->company_id,
        'name' => 'Forged Client',
    ]);

    $this->assertDatabaseMissing('clients', [
        'company_id' => $otherCompany->id,
        'name' => 'Forged Client',
    ]);
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

test('company admin employee creation ignores protected company and role payload', function () {
    $admin = companyAdminTestAdmin();
    $otherCompany = companyAdminTestCompany();

    $this->actingAs($admin)
        ->post(route('company-admin.employees.store'), [
            'name' => 'Forged Employee',
            'email' => 'forged-employee@example.test',
            'username' => 'forged_employee',
            'status' => 'active',
            'company_id' => $otherCompany->id,
            'role' => 'super_admin',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
        ])
        ->assertRedirect(route('company-admin.employees.index'));

    $this->assertDatabaseHas('users', [
        'company_id' => $admin->company_id,
        'email' => 'forged-employee@example.test',
        'role' => 'employee',
    ]);

    $this->assertDatabaseMissing('users', [
        'company_id' => $otherCompany->id,
        'email' => 'forged-employee@example.test',
    ]);
});

test('company admin cannot view another company employee workspace', function () {
    $admin = companyAdminTestAdmin();
    $otherCompany = companyAdminTestCompany();
    $employee = User::factory()->create(['company_id' => $otherCompany->id, 'role' => 'employee', 'status' => 'active']);

    $this->actingAs($admin)
        ->get(route('company-admin.employees.show', $employee))
        ->assertForbidden();
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

test('company admin project create can sync only own active team members', function () {
    $admin = companyAdminTestAdmin();
    $client = Client::create(['company_id' => $admin->company_id, 'name' => 'Team Client']);
    $ownEmployee = User::factory()->create(['company_id' => $admin->company_id, 'role' => 'employee', 'status' => 'active']);
    $otherEmployee = User::factory()->create(['company_id' => companyAdminTestCompany()->id, 'role' => 'employee', 'status' => 'active']);

    $this->actingAs($admin)
        ->post(route('company-admin.projects.store'), [
            'client_id' => $client->id,
            'manager_id' => $admin->id,
            'name' => 'Scoped Team Project',
            'status' => 'planning',
            'priority' => 'medium',
            'progress' => 0,
            'team_member_ids' => [$ownEmployee->id, $otherEmployee->id],
        ])
        ->assertSessionHasErrors('team_member_ids.1');
});

test('company admin cannot view or update another company project', function () {
    $admin = companyAdminTestAdmin();
    $otherProject = Project::create([
        'company_id' => companyAdminTestCompany()->id,
        'name' => 'Hidden External Project',
        'status' => 'active',
        'priority' => 'medium',
    ]);

    $this->actingAs($admin)
        ->get(route('company-admin.projects.show', $otherProject))
        ->assertForbidden();

    $this->actingAs($admin)
        ->put(route('company-admin.projects.update', $otherProject), [
            'name' => 'Forged External Update',
            'status' => 'active',
            'priority' => 'medium',
            'progress' => 20,
        ])
        ->assertForbidden();
});

test('company admin project filters reject another company records', function () {
    $admin = companyAdminTestAdmin();
    $otherCompany = companyAdminTestCompany();
    $otherClient = Client::create(['company_id' => $otherCompany->id, 'name' => 'Other Filter Client']);
    $otherManager = User::factory()->create(['company_id' => $otherCompany->id, 'role' => 'employee', 'status' => 'active']);

    $this->actingAs($admin)
        ->get(route('company-admin.projects.index', ['client_id' => $otherClient->id]))
        ->assertForbidden();

    $this->actingAs($admin)
        ->get(route('company-admin.projects.index', ['manager_id' => $otherManager->id]))
        ->assertForbidden();
});

test('company admin cannot assign another company employee to project', function () {
    $admin = companyAdminTestAdmin();
    $project = Project::create([
        'company_id' => $admin->company_id,
        'name' => 'Assignment Guard Project',
        'status' => 'active',
        'priority' => 'medium',
    ]);
    $otherEmployee = User::factory()->create(['company_id' => companyAdminTestCompany()->id, 'role' => 'employee', 'status' => 'active']);

    $this->actingAs($admin)
        ->post(route('company-admin.projects.assign', $project), ['user_id' => $otherEmployee->id])
        ->assertSessionHasErrors('user_id');

    expect($project->users()->whereKey($otherEmployee->id)->exists())->toBeFalse();
});

test('company admin can open project workspace with project context', function () {
    $admin = companyAdminTestAdmin();
    $employee = User::factory()->create(['company_id' => $admin->company_id, 'role' => 'employee', 'status' => 'active']);
    $project = Project::create([
        'company_id' => $admin->company_id,
        'manager_id' => $admin->id,
        'name' => 'Workspace Render Project',
        'description' => 'Workspace project description.',
        'status' => 'active',
        'priority' => 'high',
        'progress' => 0,
    ]);
    $project->users()->attach($employee->id);
    Task::create([
        'company_id' => $admin->company_id,
        'project_id' => $project->id,
        'assignee_id' => $employee->id,
        'title' => 'Workspace Task',
        'status' => 'in_progress',
        'priority' => 'medium',
        'progress' => 50,
        'task_type' => 'task',
    ]);

    $this->actingAs($admin)
        ->get(route('company-admin.projects.show', $project))
        ->assertOk()
        ->assertSee('Workspace Render Project')
        ->assertSee('Workspace Task')
        ->assertSee('Team')
        ->assertSee('Documents');
});

test('company admin can open project form with team context', function () {
    $admin = companyAdminTestAdmin();
    User::factory()->create(['company_id' => $admin->company_id, 'role' => 'employee', 'status' => 'active', 'name' => 'Form Team Member']);
    $project = Project::create([
        'company_id' => $admin->company_id,
        'name' => 'Workspace Form Project',
        'status' => 'planning',
        'priority' => 'medium',
        'progress' => 0,
    ]);

    $this->actingAs($admin)
        ->get(route('company-admin.projects.edit', $project))
        ->assertOk()
        ->assertSee('Workspace Form Project')
        ->assertSee('Form Team Member');
});

test('company admin project dates must not end before start', function () {
    $admin = companyAdminTestAdmin();

    $this->actingAs($admin)
        ->from(route('company-admin.projects.create'))
        ->post(route('company-admin.projects.store'), [
            'name' => 'Invalid Date Project',
            'status' => 'planning',
            'priority' => 'medium',
            'start_date' => '2026-08-10',
            'due_date' => '2026-08-09',
            'progress' => 0,
        ])
        ->assertRedirect(route('company-admin.projects.create'))
        ->assertSessionHasErrors('due_date');
});

test('project progress service calculates completed task percentage', function () {
    $admin = companyAdminTestAdmin();
    $project = Project::create([
        'company_id' => $admin->company_id,
        'name' => 'Progress Service Project',
        'status' => 'active',
        'priority' => 'medium',
        'progress' => 35,
    ]);

    expect(app(ProjectProgressService::class)->calculate($project))->toBe(35);

    foreach (['completed', 'completed', 'in_progress', 'cancelled'] as $index => $status) {
        Task::create([
            'company_id' => $admin->company_id,
            'project_id' => $project->id,
            'title' => 'Progress Task '.$index,
            'status' => $status,
            'priority' => 'medium',
            'progress' => $status === 'completed' ? 100 : 0,
            'task_type' => 'task',
        ]);
    }

    expect(app(ProjectProgressService::class)->sync($project->fresh()))->toBe(50);
    $this->assertDatabaseHas('projects', ['id' => $project->id, 'progress' => 50]);
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

test('company admin task create is scoped and notifies assignee', function () {
    Notification::fake();
    $admin = companyAdminTestAdmin();
    $project = Project::create(['company_id' => $admin->company_id, 'name' => 'Notify Project', 'status' => 'active', 'priority' => 'medium']);
    $employee = User::factory()->create(['company_id' => $admin->company_id, 'role' => 'employee', 'status' => 'active']);

    $this->actingAs($admin)
        ->post(route('company-admin.tasks.store'), [
            'project_id' => $project->id,
            'assignee_id' => $employee->id,
            'title' => 'Notify Assignee Task',
            'priority' => 'high',
            'status' => 'assigned',
            'progress' => 0,
            'task_type' => 'task',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('tasks', [
        'company_id' => $admin->company_id,
        'created_by' => $admin->id,
        'title' => 'Notify Assignee Task',
    ]);
    Notification::assertSentTo($employee, TaskNotification::class);
});

test('company admin cannot create task with another company project or assignee', function () {
    $admin = companyAdminTestAdmin();
    $otherCompany = companyAdminTestCompany();
    $otherProject = Project::create(['company_id' => $otherCompany->id, 'name' => 'Foreign Task Project', 'status' => 'active', 'priority' => 'medium']);
    $otherEmployee = User::factory()->create(['company_id' => $otherCompany->id, 'role' => 'employee', 'status' => 'active']);

    $this->actingAs($admin)
        ->post(route('company-admin.tasks.store'), [
            'project_id' => $otherProject->id,
            'assignee_id' => $otherEmployee->id,
            'title' => 'Invalid Foreign Task',
            'priority' => 'medium',
            'status' => 'todo',
            'progress' => 0,
            'task_type' => 'task',
        ])
        ->assertSessionHasErrors(['project_id', 'assignee_id']);
});

test('company admin task due date must not be before start date', function () {
    $admin = companyAdminTestAdmin();
    $project = Project::create([
        'company_id' => $admin->company_id,
        'name' => 'Date Guard Project',
        'status' => 'active',
        'priority' => 'medium',
    ]);

    $this->actingAs($admin)
        ->from(route('company-admin.tasks.create'))
        ->post(route('company-admin.tasks.store'), [
            'project_id' => $project->id,
            'title' => 'Invalid Date Task',
            'priority' => 'medium',
            'status' => 'assigned',
            'start_date' => '2026-08-10',
            'due_date' => '2026-08-09',
            'progress' => 0,
            'task_type' => 'task',
        ])
        ->assertRedirect(route('company-admin.tasks.create'))
        ->assertSessionHasErrors('due_date');
});

test('company admin cannot update status for another company task', function () {
    $admin = companyAdminTestAdmin();
    $otherProject = Project::create(['company_id' => companyAdminTestCompany()->id, 'name' => 'Foreign Status Project', 'status' => 'active', 'priority' => 'medium']);
    $task = Task::create([
        'company_id' => $otherProject->company_id,
        'project_id' => $otherProject->id,
        'title' => 'Foreign Status Task',
        'status' => 'assigned',
        'priority' => 'medium',
        'progress' => 0,
        'task_type' => 'task',
    ]);

    $this->actingAs($admin)
        ->post(route('company-admin.tasks.status', [$task, 'in_progress']))
        ->assertForbidden();
});

test('company admin can move task on kanban through valid transition', function () {
    $admin = companyAdminTestAdmin();
    $project = Project::create(['company_id' => $admin->company_id, 'name' => 'Kanban Project', 'status' => 'active', 'priority' => 'medium']);
    $task = Task::create([
        'company_id' => $admin->company_id,
        'project_id' => $project->id,
        'title' => 'Kanban Move Task',
        'status' => 'assigned',
        'priority' => 'medium',
        'progress' => 0,
        'task_type' => 'task',
    ]);

    $this->actingAs($admin)
        ->patch(route('company-admin.tasks.move', $task), ['status' => 'in_progress'])
        ->assertRedirect();

    $this->assertDatabaseHas('tasks', ['id' => $task->id, 'status' => 'in_progress']);
});

test('company admin can open task kanban board', function () {
    $admin = companyAdminTestAdmin();
    $project = Project::create(['company_id' => $admin->company_id, 'name' => 'Kanban Render Project', 'status' => 'active', 'priority' => 'medium']);
    Task::create([
        'company_id' => $admin->company_id,
        'project_id' => $project->id,
        'title' => 'Kanban Render Task',
        'status' => 'submitted',
        'priority' => 'high',
        'progress' => 100,
        'task_type' => 'task',
    ]);

    $this->actingAs($admin)
        ->get(route('company-admin.tasks.kanban'))
        ->assertOk()
        ->assertSee('Task Kanban')
        ->assertSee('Kanban Render Task')
        ->assertSee('Review');
});

test('company admin kanban rejects invalid transition', function () {
    $admin = companyAdminTestAdmin();
    $project = Project::create(['company_id' => $admin->company_id, 'name' => 'Invalid Kanban Project', 'status' => 'active', 'priority' => 'medium']);
    $task = Task::create([
        'company_id' => $admin->company_id,
        'project_id' => $project->id,
        'title' => 'Invalid Kanban Task',
        'status' => 'todo',
        'priority' => 'medium',
        'progress' => 0,
        'task_type' => 'task',
    ]);

    $this->actingAs($admin)
        ->patch(route('company-admin.tasks.move', $task), ['status' => 'completed'])
        ->assertSessionHas('error');

    expect($task->fresh()->status)->toBe('todo');
});

test('task review completion recalculates project progress and notifies assignee', function () {
    Notification::fake();
    $admin = companyAdminTestAdmin();
    $project = Project::create(['company_id' => $admin->company_id, 'name' => 'Review Progress Project', 'status' => 'active', 'priority' => 'medium']);
    $employee = User::factory()->create(['company_id' => $admin->company_id, 'role' => 'employee', 'status' => 'active']);
    $task = Task::create([
        'company_id' => $admin->company_id,
        'project_id' => $project->id,
        'assignee_id' => $employee->id,
        'title' => 'Complete Review Task',
        'status' => 'under_review',
        'priority' => 'medium',
        'progress' => 100,
        'task_type' => 'task',
    ]);
    Task::create([
        'company_id' => $admin->company_id,
        'project_id' => $project->id,
        'title' => 'Open Peer Task',
        'status' => 'in_progress',
        'priority' => 'medium',
        'progress' => 25,
        'task_type' => 'task',
    ]);

    $this->actingAs($admin)
        ->patch(route('company-admin.tasks.review', $task), ['status' => 'completed'])
        ->assertRedirect();

    expect($project->fresh()->progress)->toBe(50);
    Notification::assertSentTo($employee, TaskNotification::class);
});

test('company admin can upload task attachment', function () {
    Storage::fake('public');
    $admin = companyAdminTestAdmin();
    $project = Project::create(['company_id' => $admin->company_id, 'name' => 'Attachment Project', 'status' => 'active', 'priority' => 'medium']);
    $task = Task::create([
        'company_id' => $admin->company_id,
        'project_id' => $project->id,
        'title' => 'Attachment Task',
        'status' => 'in_progress',
        'priority' => 'medium',
        'progress' => 10,
        'task_type' => 'task',
    ]);

    $this->actingAs($admin)
        ->post(route('company-admin.tasks.files.store', $task), ['file' => UploadedFile::fake()->image('brief.jpg')])
        ->assertRedirect()
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success', 'File uploaded.');

    $file = WorkFile::where('task_id', $task->id)->firstOrFail();
    Storage::disk('public')->assertExists($file->path);
});

test('company admin task attachment download returns not found when storage file is missing', function () {
    $admin = companyAdminTestAdmin();
    $project = Project::create(['company_id' => $admin->company_id, 'name' => 'Missing Task File Project', 'status' => 'active', 'priority' => 'medium']);
    $task = Task::create([
        'company_id' => $admin->company_id,
        'project_id' => $project->id,
        'title' => 'Missing File Task',
        'status' => 'assigned',
        'priority' => 'medium',
        'task_type' => 'task',
    ]);
    $file = WorkFile::create([
        'company_id' => $admin->company_id,
        'project_id' => $project->id,
        'task_id' => $task->id,
        'uploaded_by' => $admin->id,
        'path' => 'missing/task-file.txt',
        'original_name' => 'task-file.txt',
        'mime_type' => 'text/plain',
        'size' => 12,
    ]);

    $this->actingAs($admin)
        ->get(route('company-admin.files.download', $file))
        ->assertNotFound();
});

test('company admin document filters reject another company records', function () {
    $admin = companyAdminTestAdmin();
    $otherCompany = companyAdminTestCompany();
    $otherProject = Project::create(['company_id' => $otherCompany->id, 'name' => 'Foreign Document Project', 'status' => 'active', 'priority' => 'medium']);
    $otherTask = Task::create([
        'company_id' => $otherCompany->id,
        'project_id' => $otherProject->id,
        'title' => 'Foreign Document Task',
        'status' => 'assigned',
        'priority' => 'medium',
        'task_type' => 'task',
    ]);

    $this->actingAs($admin)
        ->get(route('company-admin.documents.index', ['project_id' => $otherProject->id]))
        ->assertForbidden();

    $this->actingAs($admin)
        ->get(route('company-admin.documents.index', ['task_id' => $otherTask->id]))
        ->assertForbidden();
});

test('company admin document download returns not found when storage file is missing', function () {
    $admin = companyAdminTestAdmin();
    $file = WorkFile::create([
        'company_id' => $admin->company_id,
        'uploaded_by' => $admin->id,
        'path' => 'missing/document.txt',
        'original_name' => 'document.txt',
        'mime_type' => 'text/plain',
        'size' => 15,
    ]);

    $this->actingAs($admin)
        ->get(route('company-admin.documents.download', $file))
        ->assertNotFound();
});

test('company admin personal todos are private to owner', function () {
    $admin = companyAdminTestAdmin();
    $otherAdmin = companyAdminTestAdmin(companyAdminTestCompany());
    $todo = PersonalTodo::create([
        'company_id' => $otherAdmin->company_id,
        'user_id' => $otherAdmin->id,
        'title' => 'Other Private Todo',
        'priority' => 'medium',
        'status' => 'open',
    ]);

    $this->actingAs($admin)
        ->get(route('company-admin.todos.index'))
        ->assertOk()
        ->assertDontSee('Other Private Todo');

    $this->actingAs($admin)
        ->patch(route('company-admin.todos.update', $todo), [
            'title' => 'Forged Todo',
            'priority' => 'medium',
        ])
        ->assertForbidden();
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

test('company admin cannot view another company project request', function () {
    $admin = companyAdminTestAdmin();
    $request = ProjectRequest::create([
        'company_id' => companyAdminTestCompany()->id,
        'title' => 'Hidden External Request',
        'status' => 'pending',
    ]);

    $this->actingAs($admin)
        ->get(route('company-admin.project-requests.show', $request))
        ->assertForbidden();
});

test('project request filters reject another company client', function () {
    $admin = companyAdminTestAdmin();
    $client = Client::create(['company_id' => companyAdminTestCompany()->id, 'name' => 'External Request Client']);

    $this->actingAs($admin)
        ->get(route('company-admin.project-requests.index', ['client_id' => $client->id]))
        ->assertForbidden();
});

test('project request must use convert action for converted status', function () {
    $admin = companyAdminTestAdmin();
    $request = ProjectRequest::create([
        'company_id' => $admin->company_id,
        'title' => 'Manual Convert Block',
        'status' => 'approved',
    ]);

    $this->actingAs($admin)
        ->put(route('company-admin.project-requests.update', $request), [
            'status' => 'converted_to_project',
            'admin_note' => 'Trying to skip conversion.',
        ])
        ->assertSessionHasErrors('status');

    expect($request->fresh()->converted_project_id)->toBeNull();
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

test('invoice totals are calculated from line items instead of submitted totals', function () {
    $admin = companyAdminTestAdmin();
    $client = Client::create(['company_id' => $admin->company_id, 'name' => 'Line Item Client']);

    $this->actingAs($admin)
        ->post(route('company-admin.invoices.store'), [
            'client_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'subtotal' => 1,
            'tax' => 5,
            'total' => 1,
            'paid_amount' => 0,
            'status' => 'draft',
            'items' => [
                ['description' => 'Build sprint', 'quantity' => 2, 'unit_price' => 40],
                ['description' => 'Support block', 'quantity' => 1.5, 'unit_price' => 20],
            ],
        ])
        ->assertRedirect();

    $invoice = Invoice::where('company_id', $admin->company_id)->latest()->first();

    expect((float) $invoice->subtotal)->toBe(110.0)
        ->and((float) $invoice->tax)->toBe(5.0)
        ->and((float) $invoice->total)->toBe(115.0)
        ->and((float) $invoice->balance_amount)->toBe(115.0)
        ->and($invoice->items()->count())->toBe(2);
});

test('verified invoice payment updates invoice balance and status', function () {
    $admin = companyAdminTestAdmin();
    $client = Client::create(['company_id' => $admin->company_id, 'name' => 'Payment Sync Client']);
    $project = Project::create(['company_id' => $admin->company_id, 'client_id' => $client->id, 'name' => 'Payment Sync Project', 'status' => 'active', 'priority' => 'medium', 'progress' => 0]);
    $invoice = Invoice::create([
        'company_id' => $admin->company_id,
        'client_id' => $client->id,
        'project_id' => $project->id,
        'invoice_number' => 'SYNC-INV-001',
        'issue_date' => now()->toDateString(),
        'subtotal' => 100,
        'tax' => 0,
        'total' => 100,
        'paid_amount' => 0,
        'balance_amount' => 100,
        'status' => 'sent',
    ]);
    $payment = Payment::create([
        'company_id' => $admin->company_id,
        'client_id' => $client->id,
        'project_id' => $project->id,
        'invoice_id' => $invoice->id,
        'payment_type' => 'client_project',
        'amount' => 40,
        'method' => 'bank_transfer',
        'status' => 'proof_submitted',
    ]);

    $this->actingAs($admin)
        ->post(route('company-admin.payments.verify', $payment), ['verification_note' => 'Receipt matched.'])
        ->assertRedirect();

    $invoice->refresh();

    expect((float) $invoice->paid_amount)->toBe(40.0)
        ->and((float) $invoice->balance_amount)->toBe(60.0)
        ->and($invoice->status)->toBe('partially_paid');
});

test('company admin cannot link payment to another company invoice', function () {
    $admin = companyAdminTestAdmin();
    $otherCompany = companyAdminTestCompany();
    $otherClient = Client::create(['company_id' => $otherCompany->id, 'name' => 'External Invoice Client']);
    $invoice = Invoice::create([
        'company_id' => $otherCompany->id,
        'client_id' => $otherClient->id,
        'invoice_number' => 'EXT-INV-001',
        'issue_date' => now()->toDateString(),
        'subtotal' => 100,
        'tax' => 0,
        'total' => 100,
        'paid_amount' => 0,
        'balance_amount' => 100,
        'status' => 'sent',
    ]);

    $this->actingAs($admin)
        ->post(route('company-admin.payments.store'), [
            'invoice_id' => $invoice->id,
            'amount' => 50,
            'method' => 'bank_transfer',
            'status' => 'requested',
        ])
        ->assertNotFound();
});

test('invoice project must belong to selected client', function () {
    $admin = companyAdminTestAdmin();
    $client = Client::create(['company_id' => $admin->company_id, 'name' => 'Invoice Owner']);
    $otherClient = Client::create(['company_id' => $admin->company_id, 'name' => 'Wrong Project Owner']);
    $project = Project::create(['company_id' => $admin->company_id, 'client_id' => $otherClient->id, 'name' => 'Wrong Client Project', 'status' => 'active', 'priority' => 'medium', 'progress' => 0]);

    $this->actingAs($admin)
        ->post(route('company-admin.invoices.store'), [
            'client_id' => $client->id,
            'project_id' => $project->id,
            'issue_date' => now()->toDateString(),
            'tax' => 0,
            'paid_amount' => 0,
            'status' => 'draft',
            'items' => [
                ['description' => 'Scoped work', 'quantity' => 1, 'unit_price' => 100],
            ],
        ])
        ->assertStatus(422);
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

test('company admin report links ignore stale report query values', function () {
    $admin = companyAdminTestAdmin();

    $this->actingAs($admin)
        ->get(route('company-admin.reports.index', ['report' => 'missing-report', 'date_from' => now()->startOfMonth()->toDateString(), 'date_to' => now()->toDateString()]))
        ->assertOk()
        ->assertSee(route('company-admin.reports.show', ['report' => 'projects', 'date_from' => now()->startOfMonth()->toDateString(), 'date_to' => now()->toDateString()]))
        ->assertSee(route('company-admin.reports.export', ['report' => 'projects', 'date_from' => now()->startOfMonth()->toDateString(), 'date_to' => now()->toDateString()]))
        ->assertSee(route('company-admin.reports.pdf', ['report' => 'projects', 'date_from' => now()->startOfMonth()->toDateString(), 'date_to' => now()->toDateString()]))
        ->assertDontSee('/company-admin/reports/missing-report', false);
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

test('project performance report uses company scope and project drilldown', function () {
    $admin = companyAdminTestAdmin();
    $client = Client::create(['company_id' => $admin->company_id, 'name' => 'Report Project Client']);
    $project = Project::create(['company_id' => $admin->company_id, 'client_id' => $client->id, 'name' => 'Visible Performance Project', 'status' => 'active', 'priority' => 'medium', 'progress' => 0, 'due_date' => now()->addDays(2)->toDateString()]);
    Task::create(['company_id' => $admin->company_id, 'project_id' => $project->id, 'title' => 'Done Report Task', 'status' => 'completed', 'priority' => 'medium', 'progress' => 100, 'task_type' => 'task', 'completed_at' => now()]);
    Task::create(['company_id' => $admin->company_id, 'project_id' => $project->id, 'title' => 'Open Report Task', 'status' => 'in_progress', 'priority' => 'medium', 'progress' => 20, 'task_type' => 'task']);
    Project::create(['company_id' => companyAdminTestCompany()->id, 'name' => 'Hidden Foreign Performance Project', 'status' => 'active', 'priority' => 'medium']);

    $this->actingAs($admin)
        ->get(route('company-admin.reports.show', ['report' => 'project-performance']))
        ->assertOk()
        ->assertSee('Project Performance')
        ->assertSee('Visible Performance Project')
        ->assertSee('50%')
        ->assertSee(route('company-admin.projects.show', $project), false)
        ->assertDontSee('Hidden Foreign Performance Project');
});

test('report filters reject another company project', function () {
    $admin = companyAdminTestAdmin();
    $foreignProject = Project::create(['company_id' => companyAdminTestCompany()->id, 'name' => 'Foreign Report Filter Project', 'status' => 'active', 'priority' => 'medium']);

    $this->actingAs($admin)
        ->get(route('company-admin.reports.show', ['report' => 'project-performance', 'project_id' => $foreignProject->id]))
        ->assertForbidden();
});

test('task performance report uses workflow groups and overdue logic', function () {
    $admin = companyAdminTestAdmin();
    $project = Project::create(['company_id' => $admin->company_id, 'name' => 'Task Report Project', 'status' => 'active', 'priority' => 'medium']);
    Task::create(['company_id' => $admin->company_id, 'project_id' => $project->id, 'title' => 'Review Report Task', 'status' => 'submitted', 'priority' => 'high', 'progress' => 100, 'task_type' => 'task']);
    Task::create(['company_id' => $admin->company_id, 'project_id' => $project->id, 'title' => 'Overdue Report Task', 'status' => 'in_progress', 'priority' => 'medium', 'progress' => 40, 'task_type' => 'task', 'due_date' => now()->subDay()->toDateString()]);

    $this->actingAs($admin)
        ->get(route('company-admin.reports.show', ['report' => 'task-performance']))
        ->assertOk()
        ->assertSee('Pending Review')
        ->assertSee('Overdue Report Task')
        ->assertSee('Overdue');
});

test('financial summary report uses invoice and payment totals', function () {
    $admin = companyAdminTestAdmin();
    $client = Client::create(['company_id' => $admin->company_id, 'name' => 'Finance Report Client']);
    Invoice::create(['company_id' => $admin->company_id, 'client_id' => $client->id, 'invoice_number' => 'FIN-REPORT-001', 'issue_date' => now()->toDateString(), 'subtotal' => 200, 'tax' => 0, 'total' => 200, 'paid_amount' => 80, 'balance_amount' => 120, 'status' => 'partially_paid']);
    Payment::create(['company_id' => $admin->company_id, 'client_id' => $client->id, 'payment_type' => 'client_project', 'amount' => 80, 'method' => 'bank_transfer', 'status' => 'paid', 'paid_at' => now()->toDateString()]);

    $this->actingAs($admin)
        ->get(route('company-admin.reports.show', ['report' => 'financial-summary']))
        ->assertOk()
        ->assertSee('Total Invoiced')
        ->assertSee('200.00')
        ->assertSee('Total Paid')
        ->assertSee('80.00')
        ->assertSee('Open Balance')
        ->assertSee('120.00');
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

test('company settings ignore arbitrary keys and normalize currency', function () {
    $admin = companyAdminTestAdmin();

    $this->actingAs($admin)
        ->put(route('company-admin.settings.update'), [
            'timezone' => 'UTC',
            'currency' => 'usd',
            'invoice_prefix' => 'INV',
            'default_tax_percentage' => 7.5,
            'default_project_status' => 'planning',
            'default_task_priority' => 'medium',
            'unexpected_secret_key' => 'should-not-save',
            'work_start_time' => '08:30',
            'work_end_time' => '17:00',
            'lunch_break_minutes' => 30,
            'late_grace_minutes' => 10,
            'early_check_in_allowance_minutes' => 30,
            'early_departure_grace_minutes' => 10,
            'full_day_minutes' => 480,
            'half_day_minutes' => 240,
            'working_days' => [1, 2, 3, 4, 5],
        ])
        ->assertRedirect();

    $setting = \App\Models\CompanySetting::where('company_id', $admin->company_id)->firstOrFail();

    expect($setting->currency)->toBe('USD')
        ->and($setting->settings)->not->toHaveKey('unexpected_secret_key');
});

test('company settings reject invalid currency values', function () {
    $admin = companyAdminTestAdmin();

    $this->actingAs($admin)
        ->from(route('company-admin.settings.index'))
        ->put(route('company-admin.settings.update'), [
            'timezone' => 'UTC',
            'currency' => 'US1',
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
        ])
        ->assertRedirect(route('company-admin.settings.index'))
        ->assertSessionHasErrors('currency');
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

test('attendance correction rejects future checkout values', function () {
    $admin = companyAdminTestAdmin();
    $employee = User::factory()->create(['company_id' => $admin->company_id, 'role' => 'employee', 'status' => 'active']);
    $attendance = Attendance::create(['company_id' => $admin->company_id, 'user_id' => $employee->id, 'attendance_date' => now()->toDateString(), 'check_in_time' => now()->subHour(), 'status' => 'present']);

    $this->actingAs($admin)
        ->patch(route('company-admin.attendance.update', $attendance), [
            'check_in_time' => now()->subHour()->format('Y-m-d H:i:s'),
            'check_out_time' => now()->addHour()->format('Y-m-d H:i:s'),
            'correction_reason' => 'Future checkout should fail.',
        ])
        ->assertSessionHasErrors('check_out_time');
});

test('company admin cannot correct another company attendance', function () {
    $admin = companyAdminTestAdmin();
    $otherCompany = companyAdminTestCompany();
    $otherEmployee = User::factory()->create(['company_id' => $otherCompany->id, 'role' => 'employee', 'status' => 'active']);
    $attendance = Attendance::create(['company_id' => $otherCompany->id, 'user_id' => $otherEmployee->id, 'attendance_date' => now()->toDateString(), 'status' => 'present']);

    $this->actingAs($admin)
        ->patch(route('company-admin.attendance.update', $attendance), [
            'check_in_time' => now()->subHours(8)->format('Y-m-d H:i:s'),
            'check_out_time' => now()->subHour()->format('Y-m-d H:i:s'),
            'correction_reason' => 'Trying to cross tenants.',
        ])
        ->assertForbidden();
});

test('company admin can filter work sessions by task and pending manual status', function () {
    $admin = companyAdminTestAdmin();
    $employee = User::factory()->create(['company_id' => $admin->company_id, 'role' => 'employee', 'status' => 'active']);
    $project = Project::create(['company_id' => $admin->company_id, 'name' => 'Manual Review Project', 'status' => 'active', 'priority' => 'medium']);
    $task = Task::create(['company_id' => $admin->company_id, 'project_id' => $project->id, 'assignee_id' => $employee->id, 'title' => 'Visible Manual Task', 'status' => 'in_progress', 'priority' => 'medium', 'task_type' => 'task']);
    $hiddenTask = Task::create(['company_id' => $admin->company_id, 'project_id' => $project->id, 'assignee_id' => $employee->id, 'title' => 'Hidden Timer Task', 'status' => 'in_progress', 'priority' => 'medium', 'task_type' => 'task']);

    WorkSession::create(['company_id' => $admin->company_id, 'user_id' => $employee->id, 'project_id' => $project->id, 'task_id' => $task->id, 'started_at' => now()->subHours(3), 'ended_at' => now()->subHours(2), 'duration_minutes' => 60, 'status' => 'stopped', 'is_manual' => true, 'approval_status' => 'pending']);
    WorkSession::create(['company_id' => $admin->company_id, 'user_id' => $employee->id, 'project_id' => $project->id, 'task_id' => $hiddenTask->id, 'started_at' => now()->subHours(2), 'ended_at' => now()->subHour(), 'duration_minutes' => 60, 'status' => 'stopped']);

    $this->actingAs($admin)
        ->get(route('company-admin.work-sessions.index', ['task_id' => $task->id, 'status' => 'manual_pending']))
        ->assertOk()
        ->assertSee('Visible Manual Task')
        ->assertSee('Manual')
        ->assertSee('Pending');
});

test('company admin leave review notifies employee and cannot be repeated', function () {
    Notification::fake();
    $admin = companyAdminTestAdmin();
    $employee = User::factory()->create(['company_id' => $admin->company_id, 'role' => 'employee', 'status' => 'active']);
    $leave = LeaveRequest::create([
        'company_id' => $admin->company_id,
        'user_id' => $employee->id,
        'leave_type' => 'annual',
        'start_date' => now()->addDays(2)->toDateString(),
        'end_date' => now()->addDays(3)->toDateString(),
        'total_days' => 2,
        'reason' => 'Family event',
        'status' => 'pending',
    ]);

    $this->actingAs($admin)
        ->patch(route('company-admin.leave-requests.review', $leave), ['status' => 'approved', 'review_note' => 'Approved.'])
        ->assertRedirect();

    $this->assertDatabaseHas('leave_requests', ['id' => $leave->id, 'status' => 'approved', 'reviewed_by' => $admin->id]);
    Notification::assertSentTo($employee, LeaveRequestStatusNotification::class);

    $this->actingAs($admin)
        ->patch(route('company-admin.leave-requests.review', $leave->fresh()), ['status' => 'rejected'])
        ->assertForbidden();
});

test('company admin cannot review another company leave request', function () {
    $admin = companyAdminTestAdmin();
    $otherCompany = companyAdminTestCompany();
    $employee = User::factory()->create(['company_id' => $otherCompany->id, 'role' => 'employee', 'status' => 'active']);
    $leave = LeaveRequest::create([
        'company_id' => $otherCompany->id,
        'user_id' => $employee->id,
        'leave_type' => 'sick',
        'start_date' => now()->addDays(2)->toDateString(),
        'end_date' => now()->addDays(2)->toDateString(),
        'total_days' => 1,
        'reason' => 'Medical appointment',
        'status' => 'pending',
    ]);

    $this->actingAs($admin)
        ->patch(route('company-admin.leave-requests.review', $leave), ['status' => 'approved'])
        ->assertForbidden();
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
