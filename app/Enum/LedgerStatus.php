<?php

namespace App\Enum;

enum LedgerStatus: string
{
    case PENDING = 'pending';
    case PARTIALLY_PAID = 'partially_paid';
    case PAID = 'paid';
    case OVERDUE = 'overdue';
    case SUBMITTED = 'submitted';
}
