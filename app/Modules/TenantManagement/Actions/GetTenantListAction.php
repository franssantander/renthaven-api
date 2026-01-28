<?php

namespace App\Modules\TenantManagement\Actions;

use App\Modules\TenantManagement\Models\Lease;

class GetTenantListAction
{
    public function execute(array $params)
    {
        return Lease::with(['property', 'user'])
            // ->forUser(auth()->user())
            ->filter($params);
    }
}