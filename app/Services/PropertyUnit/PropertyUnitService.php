<?php

namespace App\Services\PropertyUnit;

use App\Enum\PropertyUnitStatus;
use App\Models\Property;
use App\Models\PropertyUnit;
use App\Models\TenantBusiness;
use App\Support\UuidResolver;
use Illuminate\Support\Facades\DB;

class PropertyUnitService
{
    /**
     * Create one or more property units for the given property.
     *
     * @param  array<int, array<string, mixed>>  $units
     * @return PropertyUnit[]
     */
    public function createMany(int $propertyId, array $units): array
    {
        return DB::transaction(function () use ($propertyId, $units) {
            $property = Property::findOrFail($propertyId);

            // Lock the tenant's row so concurrent "create units" requests are
            // serialized and can't each pass the max_units check individually
            // while jointly exceeding it (mirrors LeaseService::assignTenants).
            DB::table('tenant_businesses')->where('id', $property->tenant_business_id)->lockForUpdate()->value('id');

            $plan = TenantBusiness::find($property->tenant_business_id)?->plan;
            $currentUnitCount = PropertyUnit::whereHas('property', fn ($query) => $query->where('tenant_business_id', $property->tenant_business_id))->count();

            abort_if(
                $plan && $plan->max_units > 0 && $currentUnitCount + count($units) > $plan->max_units,
                422,
                "Plan limit reached. Your current plan allows a maximum of {$plan->max_units} units."
            );

            $created = [];

            foreach ($units as $unit) {
                $propertyUnit = PropertyUnit::create([
                    'property_id' => $propertyId,
                    'name' => $unit['name'],
                    'capacity' => $unit['capacity'],
                    'rent_price' => $unit['rent_price'],
                    'status' => $unit['status'] ?? PropertyUnitStatus::AVAILABLE->value,
                ]);

                if (! empty($unit['amenity_uuids'])) {
                    $propertyUnit->amenities()->sync(UuidResolver::ids('amenities', $unit['amenity_uuids']));
                }

                $created[] = $propertyUnit;
            }

            return $created;
        });
    }
}
