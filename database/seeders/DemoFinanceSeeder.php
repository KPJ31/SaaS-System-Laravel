<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoFinanceSeeder extends Seeder
{
    public function run(): void
    {
        foreach (DemoCompanySeeder::companies() as $index => $companyData) {
            $company = Company::where('email', $companyData['email'])->firstOrFail();
            $admin = User::where('company_id', $company->id)->where('role', 'company_admin')->firstOrFail();
            $client = Client::where('company_id', $company->id)->firstOrFail();
            $project = Project::where('company_id', $company->id)->firstOrFail();
            $subscription = Subscription::with('plan')->where('company_id', $company->id)->firstOrFail();

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
                    'transaction_reference' => 'CLIENT-PAY-00'.($index + 1),
                    'payment_type' => 'client_project',
                    'amount' => 1000,
                    'method' => 'bank_transfer',
                    'status' => 'received',
                    'paid_at' => now()->subDays(2)->toDateString(),
                ]
            );

            Payment::updateOrCreate(
                ['transaction_reference' => 'SUB-PAY-00'.($index + 1)],
                [
                    'company_id' => $company->id,
                    'subscription_id' => $subscription->id,
                    'subscription_plan_id' => $subscription->subscription_plan_id,
                    'created_by' => $admin->id,
                    'payment_type' => 'subscription',
                    'amount' => $subscription->monthly_price,
                    'method' => 'bank_transfer',
                    'status' => 'verified',
                    'verified_by' => User::where('role', 'super_admin')->value('id'),
                    'verified_at' => now()->subDays(3),
                    'verification_note' => 'Demo subscription payment verified.',
                    'paid_at' => now()->subDays(3)->toDateString(),
                ]
            );
        }
    }
}
