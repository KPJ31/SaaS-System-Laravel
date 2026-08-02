<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Company;
use Illuminate\Database\Seeder;

class DemoClientSeeder extends Seeder
{
    public function run(): void
    {
        foreach (DemoCompanySeeder::companies() as $index => $companyData) {
            $company = Company::where('email', $companyData['email'])->firstOrFail();

            Client::updateOrCreate(
                ['company_id' => $company->id, 'email' => 'client'.($index + 1).'@example.test'],
                [
                    'name' => $index === 0 ? 'Orion Retail Group' : 'Cobalt Finance',
                    'company_name' => $index === 0 ? 'Orion Retail Group' : 'Cobalt Finance',
                    'phone' => '+1 555 020'.($index + 1),
                    'address' => 'Client Avenue',
                    'status' => 'active',
                ]
            );
        }
    }
}
