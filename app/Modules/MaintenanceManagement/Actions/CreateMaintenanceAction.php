<?php

namespace App\Modules\MaintenanceManagement\Actions;

use App\Modules\MaintenanceManagement\Models\MaintenanceProperty;

class CreateMaintenanceAction
{
    public function execute(array $params)
    {
        $auth = auth()->user();
        $params['portfolio_id'] = $auth->portfolio_id;
        return MaintenanceProperty::create($params);
    }
}