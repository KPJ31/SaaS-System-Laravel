<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            SystemSettingSeeder::class,
            SubscriptionPlanSeeder::class,
            SuperAdminSeeder::class,
            DemoCompanySeeder::class,
            DemoSubscriptionSeeder::class,
            DemoUserSeeder::class,
            DemoClientSeeder::class,
            DemoProjectSeeder::class,
            DemoFinanceSeeder::class,
            DemoWorkSessionSeeder::class,
            CompanyRegistrationRequestSeeder::class,
            AuditLogSeeder::class,
        ]);
    }
}
