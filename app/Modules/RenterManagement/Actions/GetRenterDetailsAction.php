<?php

namespace App\Modules\RenterManagement\Actions;

class GetRenterDetailsAction
{
    public function execute($lease)
    {
        return $lease->load(['user', 'property']);
    }
}