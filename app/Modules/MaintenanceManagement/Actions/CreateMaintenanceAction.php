<?php

namespace App\Modules\MaintenanceManagement\Actions;

use App\Modules\MaintenanceManagement\Models\MaintenanceProperty;
use App\Modules\Property\Models\Property;
use App\Modules\TenantManagement\Models\Lease;

class CreateMaintenanceAction
{
    public function execute(array $params)
    {
        $property = Property::where('uuid', $params['property_id'])->firstOrFail();
        $lease = Lease::where('uuid', $params['lease_id'])->firstOrFail();
        $auth = auth()->user();

        $params['property_id'] = $property->id;
        $params['lease_id'] = $lease->id;
        $params['portfolio_id'] = $auth->portfolio_id;
        return MaintenanceProperty::create($params);
    }
}