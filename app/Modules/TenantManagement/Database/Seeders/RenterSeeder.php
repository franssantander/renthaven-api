<?php

namespace Modules\TenantManagement\Database\Seeders;

use App\Modules\Tenant\Models\Tenant;
use App\Modules\TenantManagement\Models\Renter;
use Illuminate\Database\Seeder;

class RenterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->command->warn("No Tenants found. Please seed Tenants before Renters.");
            return;
        }

        foreach ($tenants as $tenant) {
            Renter::factory()
                ->count(50)
                ->create([
                    'tenant_id' => $tenant->id,
                ]);
        }

        $this->command->info("Seeded 10 renters for each of the {$tenants->count()} tenants.");
    }
}
