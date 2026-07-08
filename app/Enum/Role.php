<?php

namespace App\Enum;

enum Role: string
{
    case SUPER_ADMIN = 'super_admin';
    case SYSTEM_ADMIN = 'system_admin';
    case ADMIN = 'admin';
    case STAFF = 'staff';
    case TENANT = 'tenant';
}