<?php

namespace App\Enum;

enum PropertyUnitStatus: string
{
    case AVAILABLE = 'available';
    case OCCUPIED = 'occupied';
    case PARTIALLY_OCCUPIED = 'partially_occupied';
    case FULL = 'full';
    case MAINTENANCE = 'maintenance';
}