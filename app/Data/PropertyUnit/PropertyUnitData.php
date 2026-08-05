<?php

namespace App\Data\PropertyUnit;

use App\Data\Amenity\AmenityData;
use App\Data\Property\PropertyData;
use App\Data\PropertyAttachment\PropertyAttachmentData;
use App\Data\Renter\RenterData;
use App\Enum\PropertyUnitStatus;
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
        public PropertyUnitStatus $status,
        public ?PropertyData $property,
        public int $occupied_count,

        #[DataCollectionOf(RenterData::class)]
        public array $tenants,

        #[DataCollectionOf(AmenityData::class)]
        public ?array $amenities,

        #[DataCollectionOf(PropertyAttachmentData::class)]
        public ?array $attachments,
    ) {}
}
