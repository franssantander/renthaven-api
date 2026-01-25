<?php

namespace App\Modules\MaintenanceManagement\Actions;

use App\Modules\MaintenanceManagement\Models\MaintenanceProperty;

class GetMaintenanceListAction
{
    public function execute(array $params)
    {
        return MaintenanceProperty::with(['property', 'lease'])
            ->forUser(auth()->user())
            ->filter($params);
    }
}