<?php

namespace Modules\RenterManagement\Database\Seeders;

use App\Modules\RenterManagement\Models\Renter;
use App\Modules\Tenant\Models\Tenant;
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
