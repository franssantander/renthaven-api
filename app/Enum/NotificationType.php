<?php

namespace App\Enum;

enum NotificationType: string
{
    case MAINTENANCE_SUBMITTED = 'maintenance_submitted';
    case PAYMENT_SUBMITTED = 'payment_submitted';
    case PAYMENT_ACCEPTED = 'payment_accepted';
    case PAYMENT_REJECTED = 'payment_rejected';
    case USER_CREATED = 'user_created';
    case TENANT_ASSIGNED = 'tenant_assigned';

    public function targetsRenter(): bool
    {
        return in_array($this, [self::PAYMENT_ACCEPTED, self::PAYMENT_REJECTED], true);
    }
}
