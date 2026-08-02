<?php

namespace App\Services\PropertyUnit;

use App\Enum\PropertyUnitStatus;
use App\Models\PropertyUnit;
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
            $created = [];

            foreach ($units as $unit) {
                $propertyUnit = PropertyUnit::create([
                    'property_id' => $propertyId,
                    'name'        => $unit['name'],
                    'capacity'    => $unit['capacity'],
                    'rent_price'  => $unit['rent_price'],
                    'status'      => $unit['status'] ?? PropertyUnitStatus::AVAILABLE->value,
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