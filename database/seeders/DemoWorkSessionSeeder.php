<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkSession;
use Illuminate\Database\Seeder;

class DemoWorkSessionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (DemoCompanySeeder::companies() as $companyData) {
            $company = Company::where('email', $companyData['email'])->firstOrFail();
            $employee = User::where('company_id', $company->id)->where('role', 'employee')->firstOrFail();
            $project = Project::where('company_id', $company->id)->firstOrFail();
            $task = Task::where('company_id', $company->id)->firstOrFail();

            WorkSession::updateOrCreate(
                ['company_id' => $company->id, 'user_id' => $employee->id, 'task_id' => $task->id, 'started_at' => now()->subDays(1)->setTime(9, 0)],
                [
                    'project_id' => $project->id,
                    'ended_at' => now()->subDays(1)->setTime(11, 30),
                    'duration_minutes' => 150,
                    'notes' => 'Demo dashboard widget work session.',
                ]
            );
        }
    }
}
