<?php

namespace App\Modules\Property\Actions;

use App\Modules\Property\Models\Property;

class GetPropertyDetailAction
{
    public function execute(Property $property)
    {
        return $property->load(['portfolio', 'createdBy', 'updatedBy']);
    }
}