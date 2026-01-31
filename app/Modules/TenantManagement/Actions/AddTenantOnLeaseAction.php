<?php

namespace App\Modules\TenantManagement\Actions;

use App\Modules\Authentication\Models\User;
use App\Modules\Property\Models\Property;
use App\Modules\TenantManagement\Models\Lease;

class AddTenantOnLeaseAction
{
    public function execute(array $params)
    {
        $userId = User::where('uuid', $params['user_uuid'])->first()->id;
        $propertyId = Property::where('uuid', $params['property_uuid'])->first()->id;

        $params['user_id'] = $userId;
        $params['property_id'] = $propertyId;
        return Lease::where('portfolio_id', auth()->user()->portfolio_id)->create($params);
    }
}