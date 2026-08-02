<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CompanySetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoCompanySeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->companies() as $index => $companyData) {
            $company = Company::updateOrCreate(
                ['email' => $companyData['email']],
                [
                    'name' => $companyData['name'],
                    'slug' => Str::slug($companyData['name']),
                    'phone' => '+1 555 010'.($index + 1),
                    'address' => ($index + 12).' Innovation Drive, Tech City',
                    'website' => 'https://example.test',
                    'status' => 'active',
                ]
            );

            CompanySetting::updateOrCreate(
                ['company_id' => $company->id],
                ['timezone' => 'Asia/Colombo', 'currency' => 'USD']
            );
        }
    }

    public static function companies(): array
    {
        return [
            ['name' => 'NovaStack Software', 'email' => 'admin@novastack.test'],
            ['name' => 'BrightForge Labs', 'email' => 'admin@brightforge.test'],
        ];
    }
}
