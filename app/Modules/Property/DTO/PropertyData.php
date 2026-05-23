<?php

namespace App\Modules\Property\DTO;

use App\Modules\Property\DTO\AmenitiesData;
use App\Modules\Property\Models\Property;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class PropertyData extends Data
{
    public function __construct(
        public int $id,
        public string $uuid,
        public string $name,
        public string $type,
        public string $description,
        public string $address_line_1,
        public ?string $address_line_2,
        public string $city,
        public string $state,
        public string $zip_code,
        public int $number_of_rooms,
        public mixed $number_of_bathrooms,
        public int $area_sq_ft,
        public mixed $monthly_rent_price,
        public mixed $security_deposit,

        public bool $is_shared,
        public int $total_units,
        public int $pax,
        public int $occupied,

        public bool $is_available,
        public bool $is_active,
        public bool $has_parking,
        public bool $allows_pets,
        public string $contact_email,
        public string $contact_phone,
        public ?string $tenant_name,
            // public ?PortfolioData $portfolio,

        #[DataCollectionOf(AmenitiesData::class)]
        public ?DataCollection $amenities,

        public ?string $created_by,
        public ?string $updated_by,
    ) {
    }

    public static function fromModel(Property $property): self
    {

        return new self(
            id: $property->id,
            uuid: $property->uuid,
            name: $property->name,
            type: $property->type,
            description: $property->description,
            address_line_1: $property->address_line_1,
            address_line_2: $property->address_line_2,
            city: $property->city,
            state: $property->state,
            zip_code: $property->zip_code,
            number_of_rooms: $property->number_of_rooms,
            number_of_bathrooms: $property->number_of_bathrooms,
            area_sq_ft: $property->area_sq_ft,
            monthly_rent_price: $property->monthly_rent_price,
            security_deposit: $property->security_deposit,
            pax: (int) $property->pax,
            is_shared: (bool) $property->is_shared,
            total_units: (int) $property->total_units,
            occupied: (int) $property->occupied,
            is_available: (bool) $property->is_available,
            is_active: (bool) $property->is_active,
            has_parking: (bool) $property->has_parking,
            allows_pets: (bool) $property->allows_pets,
            contact_email: $property->contact_email,
            contact_phone: $property->contact_phone,
            tenant_name: $property->tenant?->name ?? null,

            amenities: AmenitiesData::collect($property->amenities, DataCollection::class)
            ?? null,

            created_by: "{$property?->createdBy?->first_name} {$property?->createdBy?->last_name}" ?? null,
            updated_by: "{$property?->updatedBy?->first_name} {$property?->updatedBy?->last_name}" ?? null,
        );
    }
}