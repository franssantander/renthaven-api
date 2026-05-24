<?php

namespace App\Modules\RenterManagement\Actions;

use App\Modules\RenterManagement\Models\Lease;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateRenterLeaseAction
{
    public function execute(Lease $lease, array $params)
    {
        return DB::transaction(function () use ($lease, $params) {
            $oldStatus = (bool) $lease->is_active;
            $newStatus = isset($params['is_active']) ? (bool) $params['is_active'] : $oldStatus;
            $leaseType = $params['lease_type'] ?? $lease->lease_type;

            if ($newStatus) {
                if ($leaseType === Lease::TYPE_FIXED && empty($params['end_date'])) {
                    throw ValidationException::withMessages(['end_date' => 'End date is required for fixed-term leases.']);
                }

                if (!empty($params['unit_number']) && $params['unit_number'] !== $lease->unit_number) {
                    $isUnitTaken = Lease::where('property_id', $lease->property_id)
                        ->where('unit_number', $params['unit_number'])
                        ->where('is_active', true)
                        ->where('id', '!=', $lease->id)
                        ->exists();

                    if ($isUnitTaken) {
                        throw ValidationException::withMessages(['unit_number' => "Unit {$params['unit_number']} is occupied."]);
                    }
                }
            }

            $lease->update([
                'start_date' => $params['start_date'],
                'end_date' => ($leaseType === Lease::TYPE_FIXED) ? $params['end_date'] : null,
                'lease_type' => $leaseType,
                'unit_number' => $params['unit_number'],
                'monthly_rent' => $params['monthly_rent'] ?? $lease->monthly_rent,
                'is_active' => $newStatus,
            ]);

            if ($oldStatus !== $newStatus) {
                $property = $lease->property;
                $maxCapacity = $property->is_shared ? $property->pax : $property->total_units;

                $currentOccupancy = Lease::where('property_id', $property->id)
                    ->where('is_active', true)
                    ->count();

                $property->update([
                    'occupied' => $currentOccupancy,
                    'is_available' => $currentOccupancy < $maxCapacity
                ]);
            }

            return $lease;
        });
    }
}