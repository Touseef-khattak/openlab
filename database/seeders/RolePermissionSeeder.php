<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'patients.view',
            'patients.manage',
            'doctors.view',
            'doctors.manage',
            'catalog.view',
            'catalog.manage',
            'visits.view',
            'visits.manage',
            'results.enter',
            'results.verify',
            'reports.view',
            'reports.print',
            'reports.business',
            'audit.view',
            'users.manage',
            'settings.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Admin gets everything through Gate::before, so it carries no
        // explicit permissions here.
        $roles = [
            'admin' => [],
            'receptionist' => [
                'patients.view', 'patients.manage',
                'doctors.view',
                'catalog.view',
                'visits.view', 'visits.manage',
                'reports.view', 'reports.print',
            ],
            'technician' => [
                'patients.view',
                'catalog.view',
                'visits.view',
                'results.enter',
                'reports.view',
            ],
            'pathologist' => [
                'patients.view',
                'catalog.view',
                'visits.view',
                'results.enter', 'results.verify',
                'reports.view', 'reports.print',
            ],
        ];

        foreach ($roles as $role => $rolePermissions) {
            Role::firstOrCreate(['name' => $role])->syncPermissions($rolePermissions);
        }
    }
}
