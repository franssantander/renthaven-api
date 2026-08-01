<?php

namespace App\Services\MaintenanceRequest;

use App\Enum\MaintenancePriority;
use App\Enum\MaintenanceRequestHistoryAction;
use App\Enum\MaintenanceRequestStatus;
use App\Enum\PropertyUnitStatus;
use App\Models\Lease;
use App\Models\MaintenanceRequest;
use App\Models\MaintenanceRequestHistory;
use App\Models\PropertyUnit;
use App\Models\Renter;
use App\Notifications\MaintenanceRequestStatusChangedNotification;
use App\Services\Lease\LeaseService;
use Illuminate\Support\Facades\DB;

class MaintenanceRequestService
{
    public function __construct(protected LeaseService $leaseService) {}

    /**
     * Create a new maintenance request for a property unit.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(
        PropertyUnit $propertyUnit,
        Lease $lease,
        Renter $renter,
        int $tenantBusinessId,
        array $data,
        ?int $performedBy = null,
    ): MaintenanceRequest {
        return DB::transaction(function () use ($propertyUnit, $lease, $renter, $tenantBusinessId, $data, $performedBy) {
            $maintenanceRequest = MaintenanceRequest::create([
                'property_unit_id' => $propertyUnit->id,
                'lease_id' => $lease->id,
                'renter_id' => $renter->id,
                'tenant_business_id' => $tenantBusinessId,
                'title' => $data['title'],
                'description' => $data['description'],
                'category' => $data['category'],
                'priority' => $data['priority'] ?? MaintenancePriority::MEDIUM->value,
                'status' => MaintenanceRequestStatus::OPEN,
            ]);

            MaintenanceRequestHistory::create([
                'maintenance_request_id' => $maintenanceRequest->id,
                'action' => MaintenanceRequestHistoryAction::CREATED,
                'from_status' => null,
                'to_status' => MaintenanceRequestStatus::OPEN->value,
                'performed_by' => $performedBy,
            ]);

            return $maintenanceRequest;
        });
    }

    /**
     * Transition a maintenance request to a new status, keeping the unit's
     * occupancy/maintenance status and the request's history timeline in sync.
     */
    public function updateStatus(
        MaintenanceRequest $maintenanceRequest,
        MaintenanceRequestStatus $newStatus,
        ?int $performedBy = null,
        ?string $notes = null,
        ?int $assignedTo = null,
    ): MaintenanceRequest {
        $fromStatus = $maintenanceRequest->status;

        $terminalStatuses = [MaintenanceRequestStatus::RESOLVED, MaintenanceRequestStatus::CANCELLED];

        if (in_array($fromStatus, $terminalStatuses, true) && $newStatus !== $fromStatus) {
            abort(422, "This request is already {$fromStatus->value} and cannot be transitioned further.");
        }

        DB::transaction(function () use ($maintenanceRequest, $newStatus, $fromStatus, $performedBy, $notes, $assignedTo) {
            $updates = ['status' => $newStatus];

            if ($assignedTo !== null) {
                $updates['assigned_to'] = $assignedTo;
            }

            if ($notes !== null) {
                $updates['resolution_notes'] = $notes;
            }

            if ($newStatus === MaintenanceRequestStatus::RESOLVED) {
                $updates['resolved_at'] = now();
            }

            $maintenanceRequest->update($updates);

            $action = match ($newStatus) {
                MaintenanceRequestStatus::RESOLVED => MaintenanceRequestHistoryAction::RESOLVED,
                MaintenanceRequestStatus::CANCELLED => MaintenanceRequestHistoryAction::CANCELLED,
                default => $assignedTo !== null
                    ? MaintenanceRequestHistoryAction::ASSIGNED
                    : MaintenanceRequestHistoryAction::STATUS_CHANGED,
            };

            MaintenanceRequestHistory::create([
                'maintenance_request_id' => $maintenanceRequest->id,
                'action' => $action,
                'from_status' => $fromStatus->value,
                'to_status' => $newStatus->value,
                'performed_by' => $performedBy,
                'notes' => $notes,
            ]);
        });

        $this->syncUnitMaintenanceStatus($maintenanceRequest->propertyUnit);

        $renterUser = $maintenanceRequest->renter?->user;
        if ($renterUser) {
            $renterUser->notify(new MaintenanceRequestStatusChangedNotification($maintenanceRequest));
        }

        return $maintenanceRequest;
    }

    /**
     * Keep a property unit's status in sync with its in-progress high/urgent
     * maintenance requests: flip to 'maintenance' while any exist, otherwise
     * restore the normal occupancy-derived status.
     */
    protected function syncUnitMaintenanceStatus(PropertyUnit $propertyUnit): void
    {
        $hasActiveUrgentWork = MaintenanceRequest::where('property_unit_id', $propertyUnit->id)
            ->where('status', MaintenanceRequestStatus::IN_PROGRESS)
            ->whereIn('priority', [MaintenancePriority::HIGH, MaintenancePriority::URGENT])
            ->exists();

        if ($hasActiveUrgentWork) {
            if ($propertyUnit->status !== PropertyUnitStatus::MAINTENANCE) {
                $propertyUnit->update(['status' => PropertyUnitStatus::MAINTENANCE]);
            }

            return;
        }

        $this->leaseService->recalculateUnitStatus($propertyUnit);
    }
}
