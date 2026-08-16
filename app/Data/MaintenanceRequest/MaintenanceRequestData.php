<?php

namespace App\Data\MaintenanceRequest;

use App\Data\Lease\LeaseData;
use App\Data\PropertyUnit\PropertyUnitData;
use App\Data\Renter\RenterData;
use App\Enum\MaintenanceCategory;
use App\Enum\MaintenancePriority;
use App\Enum\MaintenanceRequestStatus;
use Illuminate\Support\Carbon;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

class MaintenanceRequestData extends Data
{
    public function __construct(
        public int $id,
        public string $uuid,
        public int $property_unit_id,
        public int $lease_id,
        public int $renter_id,
        public int $tenant_business_id,
        public string $title,
        public string $description,
        public MaintenanceCategory $category,
        public MaintenancePriority $priority,
        public MaintenanceRequestStatus $status,
        public ?int $assigned_to,
        public ?Carbon $resolved_at,
        public ?string $resolution_notes,

        public ?LeaseData $lease,
        public ?RenterData $renter,
        public ?PropertyUnitData $property_unit,

        #[DataCollectionOf(MaintenanceRequestHistoryData::class)]
        public ?array $histories,
    ) {}
}
