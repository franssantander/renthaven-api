<?php

namespace App\Enum;

enum LeaseTermType: string
{
    case FIXED_TERM = 'fixed_term';
    case MONTHLY = 'monthly';
}
