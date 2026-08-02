<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class DemoSubscriptionSeeder extends Seeder
{
    public function run(): void
    {
        $starterPlan = SubscriptionPlan::where('slug', 'starter')->firstOrFail();

        Company::whereIn('email', collect(DemoCompanySeeder::companies())->pluck('email'))->get()->each(function (Company $company) use ($starterPlan): void {
            Subscription::updateOrCreate(
                ['company_id' => $company->id, 'subscription_plan_id' => $starterPlan->id],
                [
                    'status' => 'active',
                    'starts_at' => now()->subMonth()->toDateString(),
                    'trial_ends_at' => null,
                    'renews_at' => now()->addMonth()->toDateString(),
                    'ends_at' => null,
                    'monthly_price' => $starterPlan->monthly_price,
                ]
            );
        });
    }
}
