<?php

namespace Database\Seeders;

use App\Models\CompanyRegistrationRequest;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CompanyRegistrationRequestSeeder extends Seeder
{
    public function run(): void
    {
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
