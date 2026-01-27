<?php

namespace App\Modules\MaintenanceManagement\Actions;

class DeleteMaintenanceAction
{
    public function execute($maintenanceProperty)
    {
        return $maintenanceProperty->delete();
    }
}