<?php

namespace App\Modules\Property\Actions;

use App\Modules\Property\Models\Property;

class DeletePropertyAction
{
    public function execute(Property $property)
    {
        return $property->delete();
    }
}