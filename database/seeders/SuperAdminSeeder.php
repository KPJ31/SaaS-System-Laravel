<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'superadmin@elevanix.test'],
            [
                'name' => 'Elevanix Super Admin',
                'username' => 'superadmin',
                'password' => Hash::make('Password@123'),
                'role' => 'super_admin',
                'status' => 'active',
            ]
        );
    }
}
