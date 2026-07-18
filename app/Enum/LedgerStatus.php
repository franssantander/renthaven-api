<?php

namespace App\Enum;

enum LedgerStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case OVERDUE = 'overdue';
    case SUBMITTED = 'submitted';
}
