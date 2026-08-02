<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->plans() as $plan) {
            SubscriptionPlan::updateOrCreate(
                ['slug' => $plan['slug']],
                $plan
            );
        }
    }

    private function plans(): array
    {
        return [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Core tools for small software teams.',
                'monthly_price' => 49,
                'annual_price' => 499,
                'employee_limit' => 10,
                'client_limit' => 25,
                'project_limit' => 20,
                'storage_limit_mb' => 2048,
                'trial_days' => 14,
                'features' => ['Company dashboard', 'Projects and tasks', 'Invoices and payments'],
                'status' => 'active',
                'display_order' => 1,
            ],
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'More capacity and reporting for growing companies.',
                'monthly_price' => 129,
                'annual_price' => 1290,
                'employee_limit' => 50,
                'client_limit' => 100,
                'project_limit' => 100,
                'storage_limit_mb' => 10240,
                'trial_days' => 14,
                'features' => ['Advanced reports', 'Higher usage limits', 'Priority reporting'],
                'status' => 'active',
                'display_order' => 2,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'High limits and priority support for large teams.',
                'monthly_price' => 299,
                'annual_price' => 2990,
                'employee_limit' => 250,
                'client_limit' => 500,
                'project_limit' => 500,
                'storage_limit_mb' => 102400,
                'trial_days' => 30,
                'features' => ['Enterprise limits', 'Priority support', 'Advanced analytics'],
                'status' => 'active',
                'display_order' => 3,
            ],
        ];
    }
}
