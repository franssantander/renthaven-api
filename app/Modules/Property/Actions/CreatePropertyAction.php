<?php

namespace App\Modules\Property\Actions;

use App\Modules\Property\Models\Property;
use Illuminate\Support\Facades\DB;

class CreatePropertyAction
{
    public function execute(array $params): Property
    {
        return DB::transaction(function () use ($params) {
            $amenityIds = $params['amenities'] ?? [];
            unset($params['amenities']);
            $property = Property::create($params);

            if (!empty($amenityIds)) {
                $property->amenities()->attach($amenityIds);
            }

            return $property;
        });
    }
}