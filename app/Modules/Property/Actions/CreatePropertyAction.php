<?php

namespace App\Modules\Property\Actions;

use App\Modules\Property\Models\Property;

class CreatePropertyAction
{
    public function execute($params)
    {
        return Property::create($params);
    }
}