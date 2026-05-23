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
            $properties = Property::where('tenant_id', $tenant->id)->get();
            $renters = RenterUser::where('tenant_id', $tenant->id)->get();

            if ($properties->isEmpty() || $renters->isEmpty()) {
                continue;
            }

            $renterPool = $renters->shuffle();

            foreach ($properties as $property) {
                // --- ALIGNMENT LOGIC ---
                // Determine max capacity based on the business model
                $maxCapacity = $property->is_shared ? $property->pax : $property->total_units;

                // Randomly decide how many spots to fill (from 0 to max capacity)
                $occupancyCount = rand(0, $maxCapacity);

                for ($i = 0; $i < $occupancyCount; $i++) {
                    if ($renterPool->isEmpty()) {
                        break;
                    }

                    $renter = $renterPool->pop();

                    Lease::factory()->create([
                        'renter_user_id' => $renter->id,
                        'property_id' => $property->id,
                        'tenant_id' => $tenant->id,
                        'is_active' => true,
                    ]);
                }

                // --- UPDATE PROPERTY STATE ---
                // Refresh count from the database to be sure
                $currentActiveCount = Lease::where('property_id', $property->id)
                    ->where('is_active', true)
                    ->count();

                $property->update([
                    'occupied' => $currentActiveCount,
                    'is_available' => $currentActiveCount < $maxCapacity
                ]);
            }
        }

        $this->command->info('Leases seeded: Capacities respected for both Shared and Residence types!');
    }
}