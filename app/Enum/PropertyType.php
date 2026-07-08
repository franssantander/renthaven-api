<?php

namespace App\Enum;

enum PropertyType: string
{
    case DORM = 'dorm';
    case APARTMENT = 'apartment';
    case CONDO = 'condo'; 
    case TOWNHOUSE = 'town_house';
}