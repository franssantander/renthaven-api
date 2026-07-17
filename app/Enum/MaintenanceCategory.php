<?php

namespace App\Enum;

enum MaintenanceCategory: string
{
    case PLUMBING = 'plumbing';
    case ELECTRICAL = 'electrical';
    case HVAC = 'hvac';
    case APPLIANCE = 'appliance';
    case STRUCTURAL = 'structural';
    case PEST_CONTROL = 'pest_control';
    case OTHER = 'other';
}
