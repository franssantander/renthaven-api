<?php

namespace App\Modules\RenterManagement\Actions;
use App\Modules\RenterManagement\Models\Lease;

class GetRenterListAction
{
    public function execute(array $params)
    {
        return Lease::with(['property', 'user'])
            // ->forUser(auth()->user())
            ->filter($params);
    }
}