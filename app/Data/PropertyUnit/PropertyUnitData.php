<?php

namespace App\Data\PropertyUnit;

use Spatie\LaravelData\Data;

class PropertyUnitData extends Data
{
    public function __construct(
        public int $id,
        public string $uuid,
        public int $propety_id,
        public string $name,
        public int $capacity,
        public float $rent_price,
        public string $status
    ) {}
}