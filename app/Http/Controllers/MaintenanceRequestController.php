<?php

namespace App\Http\Controllers;

use App\Data\MaintenanceRequest\MaintenanceRequestData;
use App\Enum\AuditAction;
use App\Enum\AuditModule;
use App\Enum\MaintenanceRequestStatus;
use App\Enum\Role;
use App\Http\Requests\MaintenanceRequest\StoreMaintenanceRequestRequest;
use App\Http\Requests\MaintenanceRequest\UpdateMaintenanceRequestStatusRequest;
use App\Models\Lease;
use App\Models\MaintenanceRequest;
use App\Services\AuditLog\AuditLogger;
use App\Services\MaintenanceRequest\MaintenanceRequestService;
use App\Support\UuidResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\LaravelData\PaginatedDataCollection;

class MaintenanceRequestController extends Controller
{
    public function __construct(
        protected AuditLogger $auditLogger,
        protected MaintenanceRequestService $maintenanceRequestService,
    ) {}

    /**
     * Store a newly created maintenance request.
     */
    public function store(StoreMaintenanceRequestRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        if ($user->role?->slug === Role::TENANT->value) {
            $renter = $user->renterProfile;
            abort_unless($renter, 422, 'Your account is not linked to a renter profile.');

            $lease = $renter->activeLease;
            abort_unless($lease, 422, 'You do not have an active lease to file a maintenance request against.');
        } else {
            $leaseId = UuidResolver::id('leases', $data['lease_uuid']);
            $lease = Lease::with('renter')
                ->whereHas('propertyUnit.property', function ($query) use ($user) {
                    $query->where('tenant_business_id', $user->tenant_business_id);
                })
                ->find($leaseId);

            abort_unless($lease, 404);

            $renter = $lease->renter;
        }

        $propertyUnit = $lease->propertyUnit;

        $maintenanceRequest = $this->maintenanceRequestService->create(
            $propertyUnit,
            $lease,
            $renter,
            $user->tenant_business_id,
            $data,
            $user->id,
        );

        $this->auditLogger->record(
            module: AuditModule::MAINTENANCE_REQUEST,
            action: AuditAction::CREATED,
            description: "Created maintenance request \"{$maintenanceRequest->title}\" for unit ID {$propertyUnit->id}",
            auditable: $maintenanceRequest,
            newValues: $maintenanceRequest->getAttributes(),
        );

        return $this->success($maintenanceRequest, 'Maintenance request submitted successfully.', 201);
    }

    /**
     * Display a listing of the tenant business's maintenance requests.
     */
    public function index(Request $request)
    {
        $tenantBusinessId = $request->user()->tenant_business_id;

        $requests = MaintenanceRequest::query()
            ->with(['lease', 'renter', 'propertyUnit'])
            ->where('tenant_business_id', $tenantBusinessId)
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->input('status'));
            })
            ->when($request->filled('priority'), function ($query) use ($request) {
                $query->where('priority', $request->input('priority'));
            })
            ->when($request->filled('property_unit_uuid'), function ($query) use ($request) {
                $query->whereHas('propertyUnit', function ($unitQuery) use ($request) {
                    $unitQuery->where('uuid', $request->input('property_unit_uuid'));
                });
            })
            ->latest()
            ->paginate($request->input('per_page', 15));

        return MaintenanceRequestData::collect($requests, PaginatedDataCollection::class);
    }

    /**
     * Display the authenticated renter's own maintenance requests.
     */
    public function mine(Request $request)
    {
        $renter = $request->user()->renterProfile;

        if (!$renter) {
            return $this->success([], 'No maintenance requests found.');
        }

        $requests = MaintenanceRequest::query()
            ->with(['lease', 'renter', 'propertyUnit'])
            ->where('renter_id', $renter->id)
            ->latest()
            ->paginate($request->input('per_page', 15));

        return MaintenanceRequestData::collect($requests, PaginatedDataCollection::class);
    }

    /**
     * Display the specified maintenance request, including its history timeline.
     */
    public function show(Request $request, MaintenanceRequest $maintenanceRequest): JsonResponse
    {
        $user = $request->user();
        $renter = $user->renterProfile;

        $ownsAsRenter = $renter && $maintenanceRequest->renter_id === $renter->id;
        $ownsAsStaff = $maintenanceRequest->tenant_business_id === $user->tenant_business_id;

        abort_unless($ownsAsRenter || $ownsAsStaff, 404);

        return $this->success($maintenanceRequest->load(['lease', 'renter', 'propertyUnit', 'histories']));
    }

    /**
     * Update the status of the specified maintenance request.
     */
    public function updateStatus(UpdateMaintenanceRequestStatusRequest $request, MaintenanceRequest $maintenanceRequest): JsonResponse
    {
        abort_unless($maintenanceRequest->tenant_business_id === $request->user()->tenant_business_id, 404);

        $data = $request->validated();
        $assignedTo = UuidResolver::id('users', $data['assigned_to_uuid'] ?? null);

        $originalValues = $maintenanceRequest->getOriginal();

        $this->maintenanceRequestService->updateStatus(
            $maintenanceRequest,
            MaintenanceRequestStatus::from($data['status']),
            $request->user()->id,
            $data['notes'] ?? null,
            $assignedTo,
        );

        $this->auditLogger->record(
            module: AuditModule::MAINTENANCE_REQUEST,
            action: AuditAction::UPDATED,
            description: "Updated maintenance request \"{$maintenanceRequest->title}\" status to {$maintenanceRequest->status->value}",
            auditable: $maintenanceRequest,
            oldValues: $originalValues,
        );

        return $this->success($maintenanceRequest, 'Maintenance request updated successfully.');
    }
}
