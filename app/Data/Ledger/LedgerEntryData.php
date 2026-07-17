<?php

namespace App\Data\Ledger;

use App\Data\Lease\LeaseData;
use App\Data\PropertyUnit\PropertyUnitData;
use App\Data\Renter\RenterData;
use App\Enum\LedgerStatus;
use Illuminate\Support\Carbon;
use Spatie\LaravelData\Data;

class LedgerEntryData extends Data
{
    public function __construct(
        public int $id,
        public string $uuid,
        public int $lease_id,
        public int $renter_id,
        public int $property_unit_id,
        public int $tenant_business_id,
        public float $amount,
        public ?Carbon $period_start,
        public ?Carbon $period_end,
        public ?Carbon $due_date,
        public LedgerStatus $status,
        public ?Carbon $paid_at,
        public ?int $paid_by,
        public ?Carbon $reminder_sent_at,
        public ?string $notes,

        public ?LeaseData $lease,
        public ?RenterData $renter,
        public ?PropertyUnitData $property_unit,
    ) {}
}
