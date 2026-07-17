<?php

namespace App\Enum;

enum MaintenanceRequestHistoryAction: string
{
    case CREATED = 'created';
    case ASSIGNED = 'assigned';
    case STATUS_CHANGED = 'status_changed';
    case RESOLVED = 'resolved';
    case CANCELLED = 'cancelled';
}
