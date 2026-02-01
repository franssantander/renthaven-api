<?php

namespace App\Modules\TenantManagement\Actions;

class DeleteTenantAction
{
    public function execute($lease)
    {
        return $lease->delete();
    }
}