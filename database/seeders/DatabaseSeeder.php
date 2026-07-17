<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            TestCatalogSeeder::class,
        ]);

        $devUsers = [
            ['Admin', 'admin@openlab.test', 'admin'],
            ['Receptionist', 'reception@openlab.test', 'receptionist'],
            ['Technician', 'tech@openlab.test', 'technician'],
            ['Pathologist', 'path@openlab.test', 'pathologist'],
        ];

        foreach ($devUsers as [$name, $email, $role]) {
            User::factory()
                ->create(['name' => $name, 'email' => $email])
                ->assignRole($role);
        }

        Doctor::factory(6)->create();
    }
}
