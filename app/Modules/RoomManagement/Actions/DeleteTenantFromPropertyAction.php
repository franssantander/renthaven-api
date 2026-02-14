<?php

namespace App\Modules\RoomManagement\Actions;

use App\Modules\Property\Models\Property;
use App\Modules\TenantManagement\Models\Lease;
use Exception;
use Illuminate\Support\Facades\DB;

class DeleteTenantFromPropertyAction
{
    public function execute(array $params)
    {
        return DB::transaction(function () use ($params) {
            if (!empty($params['clear_all']) && !empty($params['property_id'])) {
                $property = Property::where('uuid', $params['property_id'])->firstOrFail();

                $property->activeLeases()->update(['is_active' => false]);
                $property->activeLeases()->delete();
                $property->update(['is_available' => true]);

                return "All tenants cleared from property.";
            }

            if (!empty($params['ids'])) {
                $leases = Lease::whereIn('uuid', $params['ids'])->get();

                if ($leases->isEmpty()) {
                    throw new Exception("No leases found to delete.");
                }

                $propertyIds = $leases->pluck('property_id')->unique();

                Lease::whereIn('id', $leases->pluck('id'))->update(['is_active' => false]);
                Lease::whereIn('id', $leases->pluck('id'))->delete();

                foreach ($propertyIds as $propId) {
                    $property = Property::find($propId);
                    if ($property) {
                        $property->updateAvailability();
                    }
                }

                return count($params['ids']) . " tenant(s) removed successfully.";
            }
            throw new Exception("Invalid request parameters.");
        });
    }
}