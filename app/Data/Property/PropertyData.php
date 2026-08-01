<?php

namespace App\Data\Property;

use App\Data\Amenity\AmenityData;
use App\Data\PropertyAttachment\PropertyAttachmentData;
use App\Data\TenantBusinessData;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;

class PropertyData extends Data
{
    public function __construct(
        public int $id,
        public string $uuid,
        public string $name,
        public ?string $address,
        public string $type,
        public ?TenantBusinessData $tenant_business,

        #[DataCollectionOf(AmenityData::class)]
        public ?array $amenities,

        #[DataCollectionOf(PropertyAttachmentData::class)]
        public ?array $attachments,
    ) {}
}