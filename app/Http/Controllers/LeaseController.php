<?php

namespace App\Http\Controllers;

use App\Data\Lease\LeaseData;
use App\Enum\AuditAction;
use App\Enum\AuditModule;
use App\Enum\LeaseTermType;
use App\Enum\NotificationType;
use App\Http\Requests\Lease\RenewLeaseRequest;
use App\Http\Requests\Lease\StoreLeaseRequest;
use App\Http\Requests\Lease\TerminateLeaseRequest;
use App\Http\Requests\Lease\UpdateLeaseRequest;
use App\Models\Lease;
use App\Models\PropertyUnit;
use App\Services\AuditLog\AuditLogger;
use App\Services\Lease\LeaseService;
use App\Support\UuidResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Spatie\LaravelData\PaginatedDataCollection;

class LeaseController extends Controller
{
    public function __construct(
        protected AuditLogger $auditLogger,
        protected LeaseService $leaseService,
    ) {}

    /**
     * Display a listing of active leases, optionally filtered to a single
     * property unit (used to look up a tenant's lease uuid for reassignment
     * or termination).
     */
    public function index(Request $request)
    {
        $tenantBusinessId = $request->user()->tenant_business_id;
        $propertyUnitId = $request->filled('property_unit_uuid')
            ? UuidResolver::id('property_units', $request->input('property_unit_uuid'))
            : null;

        $leases = Lease::query()
            ->with(['renter', 'propertyUnit.property'])
            ->whereHas('propertyUnit.property', fn($query) => $query->where('tenant_business_id', $tenantBusinessId))
            ->when($propertyUnitId, fn($query) => $query->where('property_unit_id', $propertyUnitId))
            ->where('is_active', true)
            ->latest()
            ->paginate($request->input('per_page', 15));

        return LeaseData::collect($leases, PaginatedDataCollection::class);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage: assign one or more tenants to a property unit.
     */
    public function store(StoreLeaseRequest $request): JsonResponse
    {
        $data = $request->validated();
        $tenantBusinessId = $request->user()->tenant_business_id;

        $propertyUnit = $this->findTenantPropertyUnit($data['property_unit_uuid'], $tenantBusinessId);

        $capacityCheck = Gate::inspect('create', [Lease::class, $propertyUnit, count($data['tenants'])]);
        if ($capacityCheck->denied()) {
            return $this->error(message: $capacityCheck->message(), status: 403);
        }

        $leases = $this->leaseService->assignTenants(
            $propertyUnit,
            $data['tenants'],
            LeaseTermType::from($data['term_type']),
            $data['start_date'],
            $data['end_date'] ?? null,
            $tenantBusinessId,
            $request->user()->id,
        );

        foreach ($leases as $lease) {
            $this->auditLogger->record(
                module: AuditModule::LEASE,
                action: AuditAction::CREATED,
                description: "Assigned renter ID {$lease->renter_id} to unit ID {$lease->property_unit_id}",
                auditable: $lease,
                newValues: $lease->getAttributes(),
                context: ['notification_type' => NotificationType::TENANT_ASSIGNED->value],
            );
        }

        return $this->success(LeaseData::collect($leases), 'Tenant(s) assigned successfully.', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage: reassign a tenant's lease to a
     * different property unit. This ends the current lease and starts a new
     * one on the new unit, preserving a timeline via LeaseHistory.
     */
    public function update(UpdateLeaseRequest $request, Lease $lease): JsonResponse
    {
        $data = $request->validated();
        $tenantBusinessId = $request->user()->tenant_business_id;

        abort_unless($lease->propertyUnit?->property?->tenant_business_id === $tenantBusinessId, 404);
        abort_unless($lease->is_active, 422, 'Only an active lease can be reassigned.');

        $newPropertyUnit = $this->findTenantPropertyUnit($data['property_unit_uuid'], $tenantBusinessId);

        if ($newPropertyUnit->id === $lease->property_unit_id) {
            return $this->success(LeaseData::from($lease), 'Lease is already assigned to this unit.');
        }

        $capacityCheck = Gate::inspect('create', [Lease::class, $newPropertyUnit, 1]);
        if ($capacityCheck->denied()) {
            return $this->error(message: $capacityCheck->message(), status: 403);
        }

        $oldPropertyUnitId = $lease->property_unit_id;

        $newLease = $this->leaseService->reassignUnit(
            $lease,
            $newPropertyUnit,
            $data['move_date'] ?? null,
            $request->user()->id,
        );

        $this->auditLogger->record(
            module: AuditModule::LEASE,
            action: AuditAction::UPDATED,
            description: "Ended lease ID {$lease->id} on unit ID {$oldPropertyUnitId}",
            auditable: $lease,
            newValues: ['end_date' => $lease->end_date, 'is_active' => $lease->is_active],
        );

        $this->auditLogger->record(
            module: AuditModule::LEASE,
            action: AuditAction::CREATED,
            description: "Reassigned renter ID {$newLease->renter_id} to unit ID {$newLease->property_unit_id}",
            auditable: $newLease,
            newValues: $newLease->getAttributes(),
        );

        return $this->success(LeaseData::from($newLease), 'Tenant reassigned successfully.');
    }

    /**
     * Renew a fixed-term lease by extending its end date.
     */
    public function renew(RenewLeaseRequest $request, Lease $lease): JsonResponse
    {
        $data = $request->validated();
        $tenantBusinessId = $request->user()->tenant_business_id;

        abort_unless($lease->propertyUnit?->property?->tenant_business_id === $tenantBusinessId, 404);
        abort_unless($lease->is_active, 422, 'Only an active lease can be renewed.');
        abort_if($lease->term_type === LeaseTermType::MONTHLY, 422, 'Monthly leases do not require renewal.');
        abort_unless(
            ! $lease->end_date || Carbon::parse($data['end_date'])->gt($lease->end_date),
            422,
            'The new end date must be after the lease\'s current end date.'
        );

        $originalValues = $lease->getOriginal();

        $renewedLease = $this->leaseService->renewLease($lease, $data['end_date'], $request->user()->id);

        $this->auditLogger->record(
            module: AuditModule::LEASE,
            action: AuditAction::UPDATED,
            description: "Renewed lease ID {$lease->id} to end on {$data['end_date']}",
            auditable: $renewedLease,
            oldValues: $originalValues,
        );

        return $this->success(LeaseData::from($renewedLease), 'Lease renewed successfully.');
    }

    /**
     * Remove the specified resource from storage: terminate a lease
     * permanently (the tenant is moving out, not being reassigned).
     */
    public function destroy(TerminateLeaseRequest $request, Lease $lease): JsonResponse
    {
        $data = $request->validated();
        $tenantBusinessId = $request->user()->tenant_business_id;

        abort_unless($lease->propertyUnit?->property?->tenant_business_id === $tenantBusinessId, 404);
        abort_unless($lease->is_active, 422, 'Only an active lease can be terminated.');

        $originalValues = $lease->getOriginal();

        $terminatedLease = $this->leaseService->terminateLease(
            $lease,
            $data['move_out_date'],
            $data['deductions'] ?? [],
            $data['notes'] ?? null,
            $request->user()->id,
        );

        $this->auditLogger->record(
            module: AuditModule::LEASE,
            action: AuditAction::UPDATED,
            description: "Terminated lease ID {$lease->id} (tenant moved out)",
            auditable: $terminatedLease,
            oldValues: $originalValues,
        );

        return $this->success(LeaseData::from($terminatedLease), 'Lease terminated successfully.');
    }

    /**
     * Resolve a property unit that belongs to the given tenant business, or fail with 404.
     */
    protected function findTenantPropertyUnit(string $propertyUnitUuid, int $tenantBusinessId): PropertyUnit
    {
        return PropertyUnit::with('property')
            ->where('uuid', $propertyUnitUuid)
            ->whereHas('property', function ($query) use ($tenantBusinessId) {
                $query->where('tenant_business_id', $tenantBusinessId);
            })
            ->firstOrFail();
    }
}
