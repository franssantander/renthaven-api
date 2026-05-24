<?php

namespace App\Modules\RenterManagement\DTO;

use App\Modules\Property\DTO\PropertyData;
use App\Modules\RenterManagement\DTO\RenterUserData;
use Spatie\LaravelData\Data;


class LeaseData extends Data
{

    public function __construct(
        public int $id,
        public string $uuid,
        public string $monthly_rent,
        public string $unit_number,
        public string $lease_type,
        public string $start_date,
        public ?string $end_date,
        public bool $is_active,
        public ?string $created_at,
        public ?string $updated_at,

        public ?PropertyData $property,
        public ?RenterUserData $renter
    ) {
    }
}