<?php

namespace App\Modules\RenterManagement\Actions;

use App\Modules\RenterManagement\Models\RenterUser;

class GetAwaitingRentersAction
{
    public function execute(array $params)
    {
        return RenterUser::whereDoesntHave('leases', function ($query) {
            $query->where('is_active', true);
        })->filter($params);
    }
}