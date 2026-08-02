<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;

class AuditLogSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::where('role', 'super_admin')->firstOrFail();

        foreach (DemoCompanySeeder::companies() as $companyData) {
            $company = Company::where('email', $companyData['email'])->firstOrFail();

            AuditLog::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'action' => 'demo_data_seeded',
                    'description' => 'Seeded demo workspace for '.$company->name,
                ],
                [
                    'user_id' => $superAdmin->id,
                    'module' => 'Seeders',
                    'metadata' => ['source' => 'AuditLogSeeder'],
                ]
            );
        }
    }
}
