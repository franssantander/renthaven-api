<?php

namespace App\Modules\Property\Actions;

use App\Modules\Property\Models\Property;
use App\Modules\RenterManagement\Models\Lease;

class GetAvailableUnits
{
    public function execute(Property $property): array
    {
        $limit = $property->is_shared ? $property->pax : $property->total_units;
        $prefix = $property->is_shared ? 'Bed' : 'Unit';

        $occupiedUnits = Lease::where('property_id', $property->id)
            ->where('is_active', true)
            ->whereNotNull('unit_number')
            ->pluck('unit_number')
            ->map(fn($item) => strtolower(trim($item)))
            ->toArray();

        $unitsList = [];

        for ($i = 1; $i <= $limit; $i++) {
            $unitName = "$prefix $i";
            $isOccupied = in_array(strtolower(trim($unitName)), $occupiedUnits);

            $unitsList[] = [
                'label' => $unitName . ($isOccupied ? ' (Occupied)' : ' (Vacant)'),
                'value' => $unitName,
                'is_occupied' => $isOccupied,
            ];
        }

        return [
            'units' => $unitsList,
            'total_capacity' => $limit,
            'occupied_count' => count($occupiedUnits)
        ];
    }
}