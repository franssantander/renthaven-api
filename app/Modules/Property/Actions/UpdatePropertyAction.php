<?php

namespace App\Modules\Property\Actions;

use App\Modules\Property\Models\Property;

class UpdatePropertyAction
{
    public function execute(array $params, Property $property)
    {
        return $property->update($params);
    }
}