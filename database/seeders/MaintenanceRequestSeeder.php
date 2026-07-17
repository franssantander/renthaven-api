<?php

namespace Database\Seeders;

use App\Enum\MaintenanceCategory;
use App\Enum\MaintenancePriority;
use App\Enum\MaintenanceRequestStatus;
use App\Models\Lease;
use App\Models\User;
use App\Services\MaintenanceRequest\MaintenanceRequestService;
use Illuminate\Database\Seeder;

class MaintenanceRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $service = app(MaintenanceRequestService::class);

        $leases = Lease::where('is_active', true)
            ->whereHas('renter.user')
            ->with(['propertyUnit.property', 'renter'])
            ->get();

        if ($leases->isEmpty()) {
            $this->command->warn('No leases with a linked renter user found to seed maintenance requests.');
            return;
        }

        $staff = User::whereHas('role', function ($query) {
            $query->whereIn('slug', ['admin', 'staff']);
        })->first();

        $scenarios = [
            [
                'title'       => 'Leaky kitchen faucet',
                'description' => 'The kitchen sink faucet has been dripping constantly for the past few days.',
                'category'    => MaintenanceCategory::PLUMBING->value,
                'priority'    => MaintenancePriority::MEDIUM->value,
                'status'      => MaintenanceRequestStatus::OPEN,
            ],
            [
                'title'       => 'AC unit not cooling',
                'description' => 'The air conditioning unit runs but blows warm air. Unit is uncomfortably hot.',
                'category'    => MaintenanceCategory::HVAC->value,
                'priority'    => MaintenancePriority::HIGH->value,
                'status'      => MaintenanceRequestStatus::IN_PROGRESS,
            ],
            [
                'title'       => 'Broken entry door lock',
                'description' => 'The main entry door lock is jammed and the unit cannot be securely locked.',
                'category'    => MaintenanceCategory::STRUCTURAL->value,
                'priority'    => MaintenancePriority::URGENT->value,
                'status'      => MaintenanceRequestStatus::RESOLVED,
            ],
            [
                'title'       => 'Squeaky cabinet hinge',
                'description' => 'One of the kitchen cabinet doors squeaks loudly when opened.',
                'category'    => MaintenanceCategory::OTHER->value,
                'priority'    => MaintenancePriority::LOW->value,
                'status'      => MaintenanceRequestStatus::CANCELLED,
            ],
        ];

        foreach ($scenarios as $index => $scenario) {
            $lease = $leases[$index % $leases->count()];
            $propertyUnit = $lease->propertyUnit;
            $renter = $lease->renter;
            $tenantBusinessId = $propertyUnit->property->tenant_business_id;

            $maintenanceRequest = $service->create(
                $propertyUnit,
                $lease,
                $renter,
                $tenantBusinessId,
                $scenario,
                $staff?->id,
            );

            $targetStatus = $scenario['status'];

            if ($targetStatus === MaintenanceRequestStatus::OPEN) {
                continue;
            }

            if (in_array($targetStatus, [MaintenanceRequestStatus::IN_PROGRESS, MaintenanceRequestStatus::RESOLVED], true)) {
                $service->updateStatus(
                    $maintenanceRequest,
                    MaintenanceRequestStatus::IN_PROGRESS,
                    $staff?->id,
                    null,
                    $staff?->id,
                );
            }

            if ($targetStatus === MaintenanceRequestStatus::RESOLVED) {
                $service->updateStatus(
                    $maintenanceRequest,
                    MaintenanceRequestStatus::RESOLVED,
                    $staff?->id,
                    'Fixed and tested — replaced the lock mechanism.',
                );
            }

            if ($targetStatus === MaintenanceRequestStatus::CANCELLED) {
                $service->updateStatus(
                    $maintenanceRequest,
                    MaintenanceRequestStatus::CANCELLED,
                    $staff?->id,
                    'Tenant resolved it themselves; no longer needed.',
                );
            }
        }

        $this->command->info('Seeded maintenance requests across open/in_progress/resolved/cancelled statuses.');
    }
}
