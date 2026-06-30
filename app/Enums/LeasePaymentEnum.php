<?php

namespace App\Enums;

enum LeasePaymentEnum: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case OVERDUE = 'overdue';
    case REJECTED = 'rejected';
}
