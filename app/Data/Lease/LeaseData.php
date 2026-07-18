<?php

namespace App\Data\Lease;

use App\Data\PropertyUnit\PropertyUnitData;
use App\Enum\LeaseTermType;
use Illuminate\Support\Carbon;
use Spatie\LaravelData\Data;

class LeaseData extends Data
{
    public function __construct(
        public int $id,
        public string $uuid,
        public LeaseTermType $term_type,
        public ?Carbon $start_date,
        public ?Carbon $end_date,
        public bool $is_active,
        public ?PropertyUnitData $property_unit,
    ) {}
}
