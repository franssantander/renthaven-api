<?php

namespace App\Data\Amenity;

use App\Enum\AmenityCategory;
use Spatie\LaravelData\Data;

class AmenityData extends Data
{
    public function __construct(
        public int $id,
        public string $uuid,
        public string $name,
        public string $slug,
        public ?AmenityCategory $category,
        public ?string $icon,
    ) {}
}
