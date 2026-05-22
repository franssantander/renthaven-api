<?php

namespace App\Modules\RenterManagement\Actions;

use App\Modules\RenterManagement\Models\Lease;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class UpdateRenterDetails
{
    public function execute(Lease $lease, array $params): Lease
    {
        return DB::transaction(function () use ($lease, $params) {
            $isDeactivating = isset($params['is_active']) &&
                (bool) $params['is_active'] === false &&
                $lease->is_active === true;

            if ($isDeactivating) {
                $params['end_date'] = $params['end_date'] ?? Carbon::now()->toDateString();
            }

            $lease->update($params);
            $property = $lease->property;

            if ($property) {
                $currentOccupancy = $property->activeLeases()->where('is_active', true)->count();

                if ($currentOccupancy < $property->pax) {
                    $property->update(['is_available' => true]);
                }
            }

            return $lease->load(['property', 'renter']);
        });
    }
}