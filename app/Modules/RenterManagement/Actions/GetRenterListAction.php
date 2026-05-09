<?php

namespace App\Modules\RenterManagement\Actions;
use App\Modules\RenterManagement\Models\Lease;

class GetRenterListAction
{
    public function execute(array $params)
    {
        return Lease::with(['property', 'renter'])
            // ->forUser(auth()->user())
            ->filter($params);
    }
}