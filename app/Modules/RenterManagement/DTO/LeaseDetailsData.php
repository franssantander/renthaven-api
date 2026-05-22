<?php

namespace App\Modules\RenterManagement\DTO;

use App\Modules\Property\DTO\PropertyData;
use Spatie\LaravelData\Data;

class LeaseDetailsData extends Data
{
    public function __construct(
        public int $id,
        public string $uuid,
        public string $start_date,
        public string $end_date,
        public bool $is_active,
        public string $created_at,
        public string $updated_at,
        public ?RenterUserData $renter,
        public ?PropertyData $property,
    ) {
    }
}