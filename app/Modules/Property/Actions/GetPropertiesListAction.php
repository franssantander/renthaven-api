<?php

namespace App\Modules\Property\Actions;

use App\Modules\Property\Models\Property;

class GetPropertiesListAction
{
    public function execute(array $params)
    {
        return Property::with(['amenities', 'portfolio', 'createdBy', 'updatedBy'])
            ->forUser(auth()->user())
            ->filter($params);
    }
}