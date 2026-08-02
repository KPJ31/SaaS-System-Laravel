<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Support\PermissionCatalog;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (PermissionCatalog::groups() as $module => $group) {
            foreach ($group['permissions'] as $name => $label) {
                Permission::firstOrCreate(
                    ['name' => $name],
                    ['label' => $label, 'module' => $module, 'guard_name' => 'web'],
                );
            }
        }

        foreach (PermissionCatalog::basicEmployeeNames() as $name) {
            Permission::firstOrCreate(
                ['name' => $name],
                ['label' => str($name)->replace(['.', '-'], ' ')->title()->toString(), 'module' => 'employee', 'guard_name' => 'web'],
            );
        }

        foreach (PermissionCatalog::platformNames() as $name) {
            Permission::firstOrCreate(
                ['name' => $name],
                ['label' => str($name)->replace(['.', '-'], ' ')->title()->toString(), 'module' => 'platform', 'guard_name' => 'web'],
            );
        }
    }
}
