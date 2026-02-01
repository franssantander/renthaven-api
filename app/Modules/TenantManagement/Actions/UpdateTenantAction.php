<?php

namespace App\Modules\TenantManagement\Actions;
use App\Modules\TenantManagement\Models\Lease;

class UpdateTenantAction
{
    public function execute(array $params)
    {
        return Lease::update($params);
    }
}