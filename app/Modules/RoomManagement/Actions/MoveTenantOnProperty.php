<?php

namespace App\Modules\RoomManagement\Actions;

use App\Modules\Property\Models\Property;
use App\Modules\TenantManagement\Models\Lease;
use Illuminate\Support\Facades\DB;
use Exception;

class MoveTenantOnProperty
{
    public function execute(array $params)
    {
        return DB::transaction(function () use ($params) {
            $newProperty = Property::where('uuid', $params['property_id'])->firstOrFail();

            $leases = Lease::whereIn('uuid', $params['ids'])->get();
            $oldPropertyIds = $leases->pluck('property_id')->unique()->filter();

            foreach ($leases as $lease) {
                $lease->update([
                    'property_id' => $newProperty->id,
                    'portfolio_id' => $newProperty->portfolio_id
                ]);
            }
            $newProperty->updateAvailability();
            foreach ($oldPropertyIds as $oldId) {
                $oldProperty = Property::find($oldId);
                if ($oldProperty) {
                    $oldProperty->updateAvailability();
                }
            }

            return $leases->fresh(['property']);
        });
    }
}