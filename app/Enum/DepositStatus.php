<?php

namespace App\Enum;

enum DepositStatus: string
{
    case HELD = 'held';
    case PARTIALLY_REFUNDED = 'partially_refunded';
    case REFUNDED = 'refunded';
    case FORFEITED = 'forfeited';
}
