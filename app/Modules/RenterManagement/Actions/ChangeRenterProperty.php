<?php

namespace App\Modules\RenterManagement\Actions;

use App\Modules\Property\Models\Property;
use App\Modules\RenterManagement\Models\Lease;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChangeRenterProperty
{
    public function execute(Lease $lease, array $params)
    {
        return DB::transaction(function () use ($lease, $params) {
            $oldProperty = $lease->property;

            // 1. Identify the New Property
            $newProperty = isset($params['property_uuid'])
                ? Property::where('uuid', $params['property_uuid'])->firstOrFail()
                : $oldProperty;

            // 2. If moving to a different property, check if it has space
            if ($newProperty->id !== $oldProperty->id) {
                $this->validateCapacity($newProperty);
            }

            // 3. Update the Lease record
            if (isset($params['property_uuid'])) {
                $params['property_id'] = $newProperty->id;
                unset($params['property_uuid']);
            }

            $lease->update($params);

            // 4. Sync BOTH properties
            // Refresh counts for the property they left
            $this->syncPropertyStats($oldProperty);

            // Refresh counts for the property they joined (if different)
            if ($newProperty->id !== $oldProperty->id) {
                $this->syncPropertyStats($newProperty);
            }

            return $lease->load('property');
        });
    }

    /**
     * Helper to check if a property can take more tenants based on its business model
     */
    protected function validateCapacity(Property $property)
    {
        // Calculate Max Capacity dynamically
        $maxCapacity = $property->is_shared ? $property->pax : $property->total_units;

        $currentOccupancy = Lease::where('property_id', $property->id)
            ->where('is_active', true)
            ->count();

        if ($currentOccupancy >= $maxCapacity) {
            throw ValidationException::withMessages([
                'property_uuid' => "Target property '{$property->name}' is full. (Capacity: {$maxCapacity})"
            ]);
        }
    }

    /**
     * Helper to recalculate and save 'occupied' and 'is_available'
     */
    protected function syncPropertyStats(Property $property)
    {
        $currentOccupancy = Lease::where('property_id', $property->id)
            ->where('is_active', true)
            ->count();

        // Calculate Max Capacity dynamically
        $maxCapacity = $property->is_shared ? $property->pax : $property->total_units;

        $property->update([
            'occupied' => $currentOccupancy,
            'is_available' => $currentOccupancy < $maxCapacity
        ]);
    }
}