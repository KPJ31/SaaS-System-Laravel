<?php

use App\Models\LeaveRequest;
use App\Models\Project;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkFile;
use App\Models\WorkSession;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

test('company admin can review employee leave request', function () {
    $company = companyAdminTestCompany();
    $admin = companyAdminTestAdmin($company);
    $employee = User::factory()->create(['company_id' => $company->id, 'role' => 'employee', 'status' => 'active']);
    $leave = LeaveRequest::create([
        'company_id' => $company->id,
        'user_id' => $employee->id,
        'leave_type' => 'annual',
        'start_date' => now()->addDays(2)->toDateString(),
        'end_date' => now()->addDays(3)->toDateString(),
        'total_days' => 2,
        'reason' => 'Family event',
        'status' => 'pending',
    ]);

    $this->actingAs($admin)
        ->patch(route('company-admin.leave-requests.review', $leave), ['status' => 'approved', 'review_note' => 'Enjoy your leave.'])
        ->assertRedirect();

    $this->assertDatabaseHas('leave_requests', ['id' => $leave->id, 'status' => 'approved', 'reviewed_by' => $admin->id]);
});

test('company admin cannot review another company leave request', function () {
    $admin = companyAdminTestAdmin();
    $otherCompany = companyAdminTestCompany();
    $otherEmployee = User::factory()->create(['company_id' => $otherCompany->id, 'role' => 'employee', 'status' => 'active']);
    $leave = LeaveRequest::create([
        'company_id' => $otherCompany->id,
        'user_id' => $otherEmployee->id,
        'leave_type' => 'sick',
        'start_date' => now()->addDays(2)->toDateString(),
        'end_date' => now()->addDays(2)->toDateString(),
        'total_days' => 1,
        'reason' => 'Medical',
        'status' => 'pending',
    ]);

    $this->actingAs($admin)
        ->patch(route('company-admin.leave-requests.review', $leave), ['status' => 'approved'])
        ->assertForbidden();
});

test('company admin can upload and download company document', function () {
    Storage::fake('public');
    $admin = companyAdminTestAdmin();
    $project = Project::create(['company_id' => $admin->company_id, 'name' => 'Docs Project', 'status' => 'active']);

    $this->actingAs($admin)
        ->post(route('company-admin.documents.store'), [
            'project_id' => $project->id,
            'file' => UploadedFile::fake()->create('brief.pdf', 100, 'application/pdf'),
        ])
        ->assertRedirect();

    $file = WorkFile::where('company_id', $admin->company_id)->firstOrFail();
    Storage::disk('public')->assertExists($file->path);

    $this->actingAs($admin)->get(route('company-admin.documents.download', $file))->assertOk();
});

test('company admin can correct stopped work session with reason', function () {
    $admin = companyAdminTestAdmin();
    $employee = User::factory()->create(['company_id' => $admin->company_id, 'role' => 'employee', 'status' => 'active']);
    $session = WorkSession::create([
        'company_id' => $admin->company_id,
        'user_id' => $employee->id,
        'started_at' => now()->subHours(2),
        'ended_at' => now()->subHour(),
        'duration_minutes' => 60,
        'status' => 'stopped',
    ]);

    $this->actingAs($admin)
        ->patch(route('company-admin.work-sessions.update', $session), [
            'duration_minutes' => 75,
            'adjustment_reason' => 'Employee forgot to stop exactly on time.',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('work_sessions', ['id' => $session->id, 'duration_minutes' => 75, 'status' => 'adjusted']);
});

test('super admin can view new platform reports', function () {
    $superAdmin = User::factory()->create([
        'role' => 'super_admin',
        'status' => 'active',
        'password' => Hash::make('Password@123'),
    ]);
    $company = companyAdminTestCompany();
    $plan = SubscriptionPlan::create([
        'name' => 'Expiry Plan',
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
    ]);
    Subscription::create([
        'company_id' => $company->id,
        'subscription_plan_id' => $plan->id,
        'status' => 'active',
        'starts_at' => now()->toDateString(),
        'renews_at' => now()->addDays(6)->toDateString(),
        'monthly_price' => 49,
    ]);
    Project::create(['company_id' => $company->id, 'name' => 'Platform Project', 'status' => 'active']);

    $this->actingAs($superAdmin)->get(route('super-admin.reports.show', 'projects'))->assertOk()->assertSee('Project Monitoring Report');
    $this->actingAs($superAdmin)->get(route('super-admin.reports.show', 'subscription-expiry'))->assertOk()->assertSee('Subscription Expiry Report');
});
