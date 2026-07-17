<?php

namespace App\Data\PropertyUnit;

use App\Data\Amenity\AmenityData;
use App\Data\Property\PropertyData;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

class PropertyUnitData extends Data
{
    public function __construct(
        public int $id,
        public string $uuid,
        public int $property_id,
        public string $name,
        public int $capacity,
        public float $rent_price,
        public string $status,
        public ?PropertyData $property,

        #[DataCollectionOf(AmenityData::class)]
        public ?array $amenities,
    ) {}
}