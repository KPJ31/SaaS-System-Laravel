<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Company;
use App\Models\Project;
use App\Models\ProjectRequest;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoProjectSeeder extends Seeder
{
    public function run(): void
    {
        foreach (DemoCompanySeeder::companies() as $index => $companyData) {
            $company = Company::where('email', $companyData['email'])->firstOrFail();
            $admin = User::where('company_id', $company->id)->where('role', 'company_admin')->firstOrFail();
            $employee = User::where('company_id', $company->id)->where('role', 'employee')->firstOrFail();
            $client = Client::where('company_id', $company->id)->firstOrFail();

            ProjectRequest::updateOrCreate(
                ['company_id' => $company->id, 'title' => 'Customer Portal Upgrade'],
                [
                    'client_id' => $client->id,
                    'created_by' => $admin->id,
                    'description' => 'Upgrade client portal workflows and reporting.',
                    'status' => 'approved',
                    'expected_start_date' => now()->toDateString(),
                    'expected_end_date' => now()->addMonths(2)->toDateString(),
                    'estimated_budget' => 12000,
                ]
            );

            $project = Project::updateOrCreate(
                ['company_id' => $company->id, 'name' => 'Operations Control Hub'],
                [
                    'client_id' => $client->id,
                    'manager_id' => $admin->id,
                    'description' => 'Centralized dashboard for daily operations.',
                    'status' => 'in_progress',
                    'start_date' => now()->subWeeks(3)->toDateString(),
                    'due_date' => now()->addWeeks(5)->toDateString(),
                    'budget' => 18000,
                    'progress' => 45 + ($index * 10),
                ]
            );

            $project->users()->syncWithoutDetaching([$admin->id, $employee->id]);

            Task::updateOrCreate(
                ['company_id' => $company->id, 'project_id' => $project->id, 'title' => 'Build dashboard summary widgets'],
                [
                    'assignee_id' => $employee->id,
                    'created_by' => $admin->id,
                    'description' => 'Create project status and work hour widgets.',
                    'priority' => 'high',
                    'status' => 'in_progress',
                    'due_date' => now()->addDays(7)->toDateString(),
                ]
            );
        }
    }
}
