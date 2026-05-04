<?php

namespace App\Modules\Property\Actions;


class GetPropertyDetailAction
{
    public function execute($property)
    {
        return $property->load(['tenant', 'createdBy', 'updatedBy', 'amenities']);
    }
}