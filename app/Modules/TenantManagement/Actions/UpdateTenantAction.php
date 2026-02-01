<?php

namespace App\Modules\TenantManagement\Actions;

use App\Modules\Property\Models\Property;
use App\Modules\TenantManagement\Models\Lease;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateTenantAction
{
    public function execute(Lease $lease, array $params)
    {
        return DB::transaction(function () use ($lease, $params) {
            $oldProperty = $lease->property;

            $newProperty = isset($params['property_uuid'])
                ? Property::where('uuid', $params['property_uuid'])->firstOrFail()
                : $oldProperty;

            if ($newProperty->id !== $oldProperty->id) {
                $this->validateCapacity($newProperty);
            }

            if (isset($params['property_uuid'])) {
                $params['property_id'] = $newProperty->id;
                unset($params['property_uuid']);
            }

            $lease->update($params);

            // 4. Sync BOTH properties (The one they left and the one they joined)
            $this->syncPropertyAvailability($oldProperty);

            if ($newProperty->id !== $oldProperty->id) {
                $this->syncPropertyAvailability($newProperty);
            }

            return $lease->load('property');
        });
    }

    /**
     * Helper to check if a property can take more tenants
     */
    protected function validateCapacity(Property $property)
    {
        $occupancy = Lease::where('property_id', $property->id)
            ->where('is_active', true)
            ->count();

        if ($occupancy >= $property->pax) {
            throw ValidationException::withMessages([
                'property_uuid' => "Target property '{$property->name}' is full."
            ]);
        }
    }

    /**
     * Helper to recalculate and save 'is_available' status
     */
    protected function syncPropertyAvailability(Property $property)
    {
        $occupancy = Lease::where('property_id', $property->id)
            ->where('is_active', true)
            ->count();

        $property->update([
            'is_available' => $occupancy < $property->pax
        ]);
    }
}