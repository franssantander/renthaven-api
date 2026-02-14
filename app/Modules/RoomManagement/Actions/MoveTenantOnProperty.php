<?php

namespace App\Modules\RoomManagement\Actions;

use App\Modules\Property\Models\Property;
use Illuminate\Support\Facades\DB;
use Exception;

class MoveTenantOnProperty
{
    public function execute(array $params, $model)
    {
        if (!$model) {
            throw new Exception("Lease record not found or unauthorized.");
        }

        $oldPropertyId = $model->property_id;

        
        return DB::transaction(function () use ($params, $model, $oldPropertyId) {
            $newProperty = Property::findOrFail($params['property_id']);
            $model->update([
                'property_id' => $newProperty->id,
                'portfolio_id' => $newProperty->portfolio_id,
            ]);
            $newProperty->updateAvailability();

            if ($oldPropertyId) {
                $oldProperty = Property::find($oldPropertyId);
                if ($oldProperty) {
                    $oldProperty->updateAvailability();
                }
            }

            return $model->fresh()->load('property');
        });
    }
}