<?php

namespace App\Enum;

enum AuditModule: string
{
    case AUTH = 'auth';
    case RENTALS = 'rentals';
    case USER_MANAGEMENT = 'user_management';
    case BILLING = 'billing';
    case TENANT_BUSINESS = 'tenant_business';
    case PROPERTY = 'property';
    case PROPERTY_UNIT = 'property_unit';
    case LEASE = 'lease';
}