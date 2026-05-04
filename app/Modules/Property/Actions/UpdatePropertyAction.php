<?php

namespace App\Modules\Property\Actions;

use App\Modules\Property\Models\Property;
use Illuminate\Support\Facades\DB;

class UpdatePropertyAction
{
    public function execute(array $params, Property $property): Property
    {
        return DB::transaction(function () use ($params, $property) {
            $amenityIds = $params['amenities'] ?? null;
            unset($params['amenities']);
            $property->update($params);

            if ($amenityIds !== null) {
                $property->amenities()->sync($amenityIds);
            }

            return $property->refresh();
        });
    }
}