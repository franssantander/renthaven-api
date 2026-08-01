<?php

namespace App\Data\Lease;

use App\Data\PropertyUnit\PropertyUnitData;
use App\Data\Renter\RenterData;
use App\Enum\DepositStatus;
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
        public float $security_deposit,
        public float $advance_rent,
        public ?Carbon $advance_rent_applied_at,
        public DepositStatus $deposit_status,
        public ?array $deposit_deductions,
        public ?float $deposit_refunded_amount,
        public ?Carbon $deposit_refunded_at,
        public ?PropertyUnitData $property_unit,
        public ?RenterData $renter,
    ) {}
}
