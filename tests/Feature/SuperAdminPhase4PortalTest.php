<?php

use App\Models\Client;
use App\Models\Company;
use App\Models\CompanyRegistrationRequest;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\SystemSetting;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

function phase4Admin(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'password' => Hash::make('Password@123'),
        'role' => 'super_admin',
        'status' => 'active',
    ], $attributes));
}

function phase4Company(array $attributes = []): Company
{
    return Company::create(array_merge([
        'name' => fake()->unique()->company(),
        'slug' => fake()->unique()->slug(),
        'email' => fake()->unique()->safeEmail(),
        'phone' => '+1 555 0100',
        'address' => '100 Phase Four Street',
        'status' => 'active',
    ], $attributes));
}

function phase4Plan(array $attributes = []): SubscriptionPlan
{
    return SubscriptionPlan::create(array_merge([
        'name' => fake()->unique()->word().' Plan',
        'slug' => fake()->unique()->slug(),
        'monthly_price' => 120,
        'annual_price' => 1200,
        'employee_limit' => 20,
        'client_limit' => 20,
        'project_limit' => 20,
        'storage_limit_mb' => 2048,
        'trial_days' => 14,
        'status' => 'active',
        'display_order' => 1,
    ], $attributes));
}

test('phase 4 dashboard shows focused platform metrics and real currency', function () {
    SystemSetting::put('currency', 'LKR');
    $admin = phase4Admin(['email' => 'phase4-dashboard@example.test', 'username' => 'phase4dashboard']);
    $company = phase4Company(['name' => 'Phase Four Dashboard Co']);
    $plan = phase4Plan(['name' => 'Growth Phase 4', 'slug' => 'growth-phase-4']);
    $subscription = Subscription::create([
        'company_id' => $company->id,
        'subscription_plan_id' => $plan->id,
        'status' => 'active',
        'starts_at' => now()->subMonth()->toDateString(),
        'renews_at' => now()->addMonth()->toDateString(),
        'monthly_price' => 120,
    ]);
    Payment::create([
        'company_id' => $company->id,
        'subscription_id' => $subscription->id,
        'subscription_plan_id' => $plan->id,
        'transaction_reference' => 'PHASE4-DASH-PAID',
        'payment_type' => 'subscription',
        'amount' => 120,
        'method' => 'bank_transfer',
        'status' => 'verified',
    ]);

    $this->actingAs($admin)
        ->get(route('super-admin.dashboard'))
        ->assertOk()
        ->assertSee('Platform Dashboard')
        ->assertSee('Revenue This Month')
        ->assertSee('LKR 120.00')
        ->assertSee('Needs Attention')
        ->assertSee('Recent Companies')
        ->assertSee('data-chart="companyGrowth"', false)
        ->assertSee('data-chart="planUsage"', false);
});

test('super admin profile ignores protected fields and clears must change password', function () {
    $admin = phase4Admin(['must_change_password' => true]);

    $this->actingAs($admin)
        ->put(route('super-admin.profile.update'), [
            'name' => 'Updated Super Admin',
            'username' => 'updated-super-admin',
            'email' => 'updated-super-admin@example.test',
            'phone' => '555-4040',
            'role' => 'employee',
            'company_id' => phase4Company()->id,
            'status' => 'suspended',
        ])
        ->assertRedirect();

    $admin->refresh();

    expect($admin->name)->toBe('Updated Super Admin')
        ->and($admin->role)->toBe('super_admin')
        ->and($admin->company_id)->toBeNull()
        ->and($admin->status)->toBe('active');

    $this->actingAs($admin)
        ->put(route('super-admin.profile.password'), [
            'current_password' => 'Password@123',
            'password' => 'NewPassword@123',
            'password_confirmation' => 'NewPassword@123',
        ])
        ->assertRedirect();

    $admin->refresh();

    expect(Hash::check('NewPassword@123', $admin->password))->toBeTrue()
        ->and($admin->must_change_password)->toBeFalse();
});

test('system settings are super-admin only and ignore arbitrary keys', function () {
    $admin = phase4Admin();
    $company = phase4Company();
    $companyAdmin = User::factory()->create([
        'company_id' => $company->id,
        'role' => 'company_admin',
        'status' => 'active',
    ]);

    $this->actingAs($companyAdmin)
        ->get(route('super-admin.settings.index'))
        ->assertForbidden();

    $this->actingAs($admin)
        ->put(route('super-admin.settings.update'), [
            'platform_name' => 'Elevanix Secure',
            'platform_abbreviation' => 'EVX',
            'support_email' => 'support@example.test',
            'support_phone' => '555-5050',
            'platform_address' => 'Platform Street',
            'timezone' => 'UTC',
            'date_format' => 'Y-m-d',
            'platform_logo' => '',
            'favicon' => '',
            'primary_color' => '#2563EB',
            'login_background_image' => '',
            'registration_enabled' => '1',
            'company_approval_required' => '1',
            'trial_duration_days' => 14,
            'currency' => 'lkr',
            'subscription_reminder_days' => 7,
            'allow_trial_plan' => '1',
            'allow_plan_upgrade' => '1',
            'email_sender_name' => 'Elevanix',
            'email_sender_email' => 'sender@example.test',
            'smtp_password' => 'should-not-save',
        ])
        ->assertRedirect();

    expect(SystemSetting::getValue('currency'))->toBe('LKR')
        ->and(SystemSetting::where('key', 'smtp_password')->exists())->toBeFalse();
});

test('audit log details redact sensitive values', function () {
    $admin = phase4Admin();
    $log = AuditLog::create([
        'user_id' => $admin->id,
        'action' => 'settings_updated',
        'module' => 'System Settings',
        'description' => 'Sensitive settings test.',
        'old_values' => ['password' => 'old-secret'],
        'new_values' => ['api_token' => 'new-token'],
        'metadata' => ['nested' => ['smtp_password' => 'smtp-secret'], 'safe' => 'visible-value'],
    ]);

    $this->actingAs($admin)
        ->get(route('super-admin.audit-logs.show', $log))
        ->assertOk()
        ->assertSee('[redacted]')
        ->assertSee('visible-value')
        ->assertDontSee('old-secret')
        ->assertDontSee('new-token')
        ->assertDontSee('smtp-secret');
});

test('company workspace tabs show only selected company records', function () {
    $admin = phase4Admin(['email' => 'phase4-company@example.test', 'username' => 'phase4company']);
    $company = phase4Company(['name' => 'Visible Phase 4 Co']);
    $otherCompany = phase4Company(['name' => 'Hidden Phase 4 Co']);
    $client = Client::create(['company_id' => $company->id, 'name' => 'Visible Client', 'email' => 'visible-client@example.test', 'status' => 'active']);
    Client::create(['company_id' => $otherCompany->id, 'name' => 'Hidden Client', 'email' => 'hidden-client@example.test', 'status' => 'active']);
    Project::create(['company_id' => $company->id, 'client_id' => $client->id, 'name' => 'Visible Project', 'status' => 'active', 'priority' => 'medium', 'progress' => 40]);
    Project::create(['company_id' => $otherCompany->id, 'name' => 'Hidden Project', 'status' => 'active', 'priority' => 'medium', 'progress' => 10]);
    User::factory()->create(['company_id' => $company->id, 'role' => 'company_admin', 'status' => 'active', 'name' => 'Visible Admin']);
    User::factory()->create(['company_id' => $otherCompany->id, 'role' => 'company_admin', 'status' => 'active', 'name' => 'Hidden Admin']);

    $this->actingAs($admin)
        ->get(route('super-admin.companies.show', [$company, 'tab' => 'projects']))
        ->assertOk()
        ->assertSee('Visible Project')
        ->assertSee('Visible Client')
        ->assertDontSee('Hidden Project')
        ->assertDontSee('Hidden Client');
});

test('company registration request filters search applicant and status', function () {
    $admin = phase4Admin(['email' => 'phase4-requests@example.test', 'username' => 'phase4requests']);
    CompanyRegistrationRequest::create([
        'company_name' => 'Visible Request Co',
        'company_email' => 'visible-request@example.test',
        'company_phone' => '+1 555 1000',
        'company_address' => 'Visible Street',
        'admin_name' => 'Visible Applicant',
        'admin_email' => 'visible-applicant@example.test',
        'username' => 'visibleapplicant',
        'password' => 'Password@123',
        'status' => 'pending',
    ]);
    CompanyRegistrationRequest::create([
        'company_name' => 'Hidden Request Co',
        'company_email' => 'hidden-request@example.test',
        'company_phone' => '+1 555 2000',
        'company_address' => 'Hidden Street',
        'admin_name' => 'Hidden Applicant',
        'admin_email' => 'hidden-applicant@example.test',
        'username' => 'hiddenapplicant',
        'password' => 'Password@123',
        'status' => 'approved',
    ]);

    $this->actingAs($admin)
        ->get(route('super-admin.company-requests.index', ['search' => 'Visible Applicant', 'status' => 'pending']))
        ->assertOk()
        ->assertSee('Visible Request Co')
        ->assertDontSee('Hidden Request Co');
});

test('super admin notifications can be filtered to unread items', function () {
    $admin = phase4Admin(['email' => 'phase4-notifications@example.test', 'username' => 'phase4notifications']);
    $admin->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => 'App\\Notifications\\Phase4UnreadNotification',
        'data' => ['title' => 'Unread Phase 4 Notice', 'message' => 'Needs attention'],
        'read_at' => null,
    ]);
    $admin->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => 'App\\Notifications\\Phase4ReadNotification',
        'data' => ['title' => 'Read Phase 4 Notice', 'message' => 'Already handled'],
        'read_at' => now(),
    ]);

    $response = $this->actingAs($admin)
        ->get(route('super-admin.notifications.index', ['status' => 'unread']))
        ->assertOk()
        ->assertSee('Unread Phase 4 Notice');

    expect(substr_count($response->content(), 'Unread Phase 4 Notice'))->toBe(2)
        ->and(substr_count($response->content(), 'Read Phase 4 Notice'))->toBe(1);
});
