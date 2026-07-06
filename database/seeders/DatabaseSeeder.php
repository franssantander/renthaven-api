<?php

namespace Database\Seeders;

use Illuminate\Container\Attributes\Database;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        DatabaseSeeder::call([
            PlanSeeder::class,
            TenantBusinessSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
            PermissionModuleSeeder::class,
            PermissionActionSeeder::class,
            RolePermissionSeeder::class,
        ]);

        $this->command->info('Creating personal access client for Passport...');

        Artisan::call('passport:client', [
            '--personal' => true,
            '--name' => 'Rental Client',
            '--no-interaction' => true,
        ]);
    }
}