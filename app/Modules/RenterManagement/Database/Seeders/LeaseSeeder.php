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
                $maxCapacity = $property->is_shared ? $property->pax : $property->total_units;

                $occupancyCount = rand(0, $maxCapacity);

                for ($i = 1; $i <= $occupancyCount; $i++) {
                    if ($renterPool->isEmpty()) {
                        break;
                    }

                    $renter = $renterPool->pop();
                    $prefix = $property->is_shared ? 'Bed' : 'Unit';

                    $factory = (rand(1, 10) <= 3)
                        ? Lease::factory()->fixed()
                        : Lease::factory();

                    $factory->create([
                        'renter_user_id' => $renter->id,
                        'property_id' => $property->id,
                        'tenant_id' => $tenant->id,
                        'monthly_rent' => $property->monthly_rent_price,
                        'unit_number' => "{$prefix} {$i}",
                        'is_active' => true,
                    ]);
                }

                $currentActiveCount = Lease::where('property_id', $property->id)
                    ->where('is_active', true)
                    ->count();

                $property->update([
                    'occupied' => $currentActiveCount,
                    'is_available' => $currentActiveCount < $maxCapacity
                ]);
            }
        }

        $this->command->info('Leases seeded: Types (Fixed/Monthly) and Units are now perfectly aligned!');
    }
}