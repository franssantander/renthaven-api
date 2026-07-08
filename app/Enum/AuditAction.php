<?php

namespace App\Enum;

enum AuditAction: string
{
    case LOGIN_SUCCESS = 'login_success';
    case LOGIN_FAILED = 'login_failed';
    case LOGIN_BLOCKED_UNVERIFIED = 'login_blocked_unverified';
    case LOGOUT = 'logout';

    case CREATED = 'created';
    case UPDATED = 'updated';
    case DELETED = 'deleted';
}