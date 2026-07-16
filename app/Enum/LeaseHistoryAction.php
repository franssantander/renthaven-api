<?php

namespace App\Enum;

enum LeaseHistoryAction: string
{
    case ASSIGNED = 'assigned';
    case REASSIGNED = 'reassigned';
    case ENDED = 'ended';
}
