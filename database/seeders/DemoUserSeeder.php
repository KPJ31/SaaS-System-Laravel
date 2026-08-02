<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        foreach (DemoCompanySeeder::companies() as $index => $companyData) {
            $company = Company::where('email', $companyData['email'])->firstOrFail();

            User::updateOrCreate(
                ['email' => $companyData['email']],
                [
                    'company_id' => $company->id,
                    'name' => $company->name.' Admin',
                    'username' => $index === 0 ? 'novastack_admin' : 'brightforge_admin',
                    'password' => Hash::make('Password@123'),
                    'role' => 'company_admin',
                    'status' => 'active',
                ]
            );

            User::updateOrCreate(
                ['email' => 'employee'.($index + 1).'@elevanix.test'],
                [
                    'company_id' => $company->id,
                    'name' => $index === 0 ? 'Maya Fernando' : 'Arun Silva',
                    'username' => $index === 0 ? 'maya' : 'arun',
                    'password' => Hash::make('Password@123'),
                    'role' => 'employee',
                    'status' => 'active',
                    'employee_code' => 'EMP-00'.($index + 1),
                    'job_title' => $index === 0 ? 'Laravel Developer' : 'QA Engineer',
                    'department' => 'Engineering',
                    'join_date' => now()->subMonths(6 + $index)->toDateString(),
                ]
            );
        }
    }
}
