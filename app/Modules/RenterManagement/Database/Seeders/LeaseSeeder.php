<?php

namespace Modules\RenterManagement\Database\Seeders;

use App\Modules\Property\Models\Property;
use App\Modules\RenterManagement\Models\Lease;
use App\Modules\RenterManagement\Models\RenterUser;
use App\Modules\Tenant\Models\Tenant;
use Illuminate\Database\Seeder;

class LeaseSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            // Get all properties and renters for THIS tenant only
            $properties = Property::where('tenant_id', $tenant->id)->get();
            $renters = RenterUser::where('tenant_id', $tenant->id)->get();

            if ($properties->isEmpty() || $renters->isEmpty()) {
                continue;
            }

            // Shuffle renters to distribute them randomly
            $renterPool = $renters->shuffle();

            foreach ($properties as $property) {
                // Determine how many people to put in this property (from 0 to pax)
                $occupancyCount = rand(0, $property->pax);

                for ($i = 0; $i < $occupancyCount; $i++) {
                    // Check if we still have renters available in the pool
                    if ($renterPool->isEmpty()) {
                        break;
                    }

                    $renter = $renterPool->pop();

                    Lease::factory()->create([
                        'renter_user_id' => $renter->id,
                        'property_id' => $property->id,
                        'tenant_id' => $tenant->id,
                    ]);
                }

                // Update is_available status based on occupancy
                // If the number of active leases equals or exceeds pax, set available to false
                $currentLeasesCount = $property->activeLeases()->count();
                $property->update([
                    'is_available' => $currentLeasesCount < $property->pax
                ]);
            }
        }

        $this->command->info('Leases seeded and property availability updated successfully!');
    }
}
