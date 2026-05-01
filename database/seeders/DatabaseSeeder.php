<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Authentication\Database\Seeders\RoleSeeder;
use Modules\Plan\Database\Seeders\PlansSeeder;
use Modules\Property\Database\Seeders\AmenitySeeder;
use Modules\Property\Database\Seeders\PropertySeeder;
use Modules\Tenant\Database\Seeders\TenantSeeder;
use Modules\Tenant\Database\Seeders\TenantUserSeeder;

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
            TenantUserSeeder::class,
        ]);
    }
}