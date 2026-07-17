<?php

namespace App\Enum;

enum AmenityCategory: string
{
    case GENERAL = 'general';
    case KITCHEN = 'kitchen';
    case BATHROOM = 'bathroom';
    case OUTDOOR = 'outdoor';
    case PARKING = 'parking';
    case SAFETY = 'safety';
    case INTERNET = 'internet';
    case ENTERTAINMENT = 'entertainment';
}
