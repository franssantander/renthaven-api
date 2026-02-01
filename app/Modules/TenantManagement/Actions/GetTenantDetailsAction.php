<?php

namespace App\Modules\TenantManagement\Actions;

class GetTenantDetailsAction
{
    public function execute($lease)
    {
        return $lease->load(['user', 'property']);
    }
}