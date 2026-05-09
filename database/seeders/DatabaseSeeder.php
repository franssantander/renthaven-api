<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Modules\Authentication\Database\Seeders\RoleSeeder;
use Modules\Plan\Database\Seeders\PlansSeeder;
use Modules\Property\Database\Seeders\AmenitySeeder;
use Modules\Property\Database\Seeders\PropertySeeder;
use Modules\RenterManagement\Database\Seeders\LeaseSeeder;
use Modules\RenterManagement\Database\Seeders\RenterSeeder;
use Modules\Tenant\Database\Seeders\TenantSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            PlansSeeder::class,
            RoleSeeder::class,
            TenantSeeder::class,
            AmenitySeeder::class,
            PropertySeeder::class,
            RenterSeeder::class,
            LeaseSeeder::class
        ]);

        Artisan::call('passport:client', [
            '--personal' => true,
            '--name' => 'Renthaven Staff Client',
            '--provider' => 'users',
            '--no-interaction' => true,
        ]);

        Artisan::call('passport:client', [
            '--personal' => true,
            '--name' => 'Renthaven Renter Client',
            '--provider' => 'renters',
            '--no-interaction' => true,
        ]);

        $this->command->info('Passport clients created successfully.');
    }
}