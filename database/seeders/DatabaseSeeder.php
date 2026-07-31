<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Company;
use App\Models\CompanyRegistrationRequest;
use App\Models\CompanySetting;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Project;
use App\Models\ProjectRequest;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $superAdmin = User::updateOrCreate(
            ['email' => 'superadmin@elevanix.test'],
            [
                'name' => 'Elevanix Super Admin',
                'username' => 'superadmin',
                'password' => Hash::make('Password@123'),
                'role' => 'super_admin',
                'status' => 'active',
            ]
        );

        $companies = [
            ['name' => 'NovaStack Software', 'email' => 'admin@novastack.test'],
            ['name' => 'BrightForge Labs', 'email' => 'admin@brightforge.test'],
        ];

        foreach ($companies as $index => $companyData) {
            $company = Company::updateOrCreate(
                ['email' => $companyData['email']],
                [
                    'name' => $companyData['name'],
                    'phone' => '+1 555 010'.($index + 1),
                    'address' => ($index + 12).' Innovation Drive, Tech City',
                    'website' => 'https://example.test',
                    'status' => 'active',
                ]
            );

            CompanySetting::updateOrCreate(['company_id' => $company->id], ['timezone' => 'Asia/Colombo', 'currency' => 'USD']);

            $admin = User::updateOrCreate(
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

            $employee = User::updateOrCreate(
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

            $client = Client::updateOrCreate(
                ['company_id' => $company->id, 'email' => 'client'.($index + 1).'@example.test'],
                [
                    'name' => $index === 0 ? 'Orion Retail Group' : 'Cobalt Finance',
                    'company_name' => $index === 0 ? 'Orion Retail Group' : 'Cobalt Finance',
                    'phone' => '+1 555 020'.($index + 1),
                    'address' => 'Client Avenue',
                    'status' => 'active',
                ]
            );

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

            $task = Task::updateOrCreate(
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

            $invoice = Invoice::updateOrCreate(
                ['invoice_number' => 'INV-2026-00'.($index + 1)],
                [
                    'company_id' => $company->id,
                    'client_id' => $client->id,
                    'project_id' => $project->id,
                    'issue_date' => now()->subDays(5)->toDateString(),
                    'due_date' => now()->addDays(25)->toDateString(),
                    'subtotal' => 2500,
                    'tax' => 250,
                    'total' => 2750,
                    'status' => 'sent',
                ]
            );

            InvoiceItem::updateOrCreate(
                ['invoice_id' => $invoice->id, 'description' => 'Sprint delivery milestone'],
                ['quantity' => 1, 'unit_price' => 2500, 'line_total' => 2500]
            );

            Payment::updateOrCreate(
                ['company_id' => $company->id, 'invoice_id' => $invoice->id],
                [
                    'client_id' => $client->id,
                    'project_id' => $project->id,
                    'created_by' => $admin->id,
                    'amount' => 1000,
                    'method' => 'bank_transfer',
                    'status' => 'received',
                    'paid_at' => now()->subDays(2)->toDateString(),
                ]
            );

            AuditLog::create([
                'company_id' => $company->id,
                'user_id' => $superAdmin->id,
                'action' => 'demo_data_seeded',
                'description' => 'Seeded demo workspace for '.$company->name,
            ]);
        }

        CompanyRegistrationRequest::updateOrCreate(
            ['company_email' => 'hello@pendingstudio.test'],
            [
                'company_name' => 'Pending Studio',
                'company_phone' => '+1 555 0300',
                'company_address' => '88 Review Street',
                'admin_name' => 'Pending Admin',
                'admin_email' => 'admin@pendingstudio.test',
                'username' => 'pending_admin',
                'password' => Hash::make('Password@123'),
                'status' => 'pending',
            ]
        );
    }
}
