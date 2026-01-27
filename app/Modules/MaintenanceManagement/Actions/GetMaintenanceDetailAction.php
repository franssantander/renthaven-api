<?php

namespace App\Modules\MaintenanceManagement\Actions;

class GetMaintenanceDetailAction
{
    public function execute($maintenanceProperty)
    {
        return $maintenanceProperty->load(['property', 'lease']);
    }
}