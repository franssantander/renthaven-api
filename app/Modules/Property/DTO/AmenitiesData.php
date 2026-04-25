<?php

namespace App\Modules\Property\DTO;

use Spatie\LaravelData\Data;

class AmenitiesData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
    ) {
    }
}