<?php

namespace App\Modules\MaintenanceManagement\Actions;

class UpdateMaintenanceDetailAction
{
    public function execute(array $params, $maintenanceProperty)
    {
        return $maintenanceProperty->update($params);
    }
}