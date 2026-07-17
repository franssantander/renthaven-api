<?php

namespace App\Http\Controllers;

use App\Data\PropertyUnit\PropertyUnitData;
use App\Enum\AuditAction;
use App\Enum\AuditModule;
use App\Http\Requests\PropertyUnit\StorePropertyUnitRequest;
use App\Http\Requests\PropertyUnit\SyncAmenitiesRequest;
use App\Http\Requests\PropertyUnit\UpdatePropertyUnitRequest;
use App\Models\PropertyUnit;
use App\Services\AuditLog\AuditLogger;
use App\Services\DashboardMetricService;
use App\Services\PropertyUnit\PropertyUnitService;
use App\Support\UuidResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\LaravelData\PaginatedDataCollection;

class PropertyUnitController extends Controller
{

    public function __construct(
        protected AuditLogger $auditLogger,
        protected PropertyUnitService $propertyUnitService,
        protected DashboardMetricService $metricService,
    ) {}

    /**
     * Display dashboard metrics for the tenant's property units.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_business_id;

        $unitQuery = PropertyUnit::query()->whereHas('property', function ($query) use ($tenantId) {
            $query->where('tenant_business_id', $tenantId);
        });

        $widgets = [
            $this->metricService->buildCountMetric(
                title: 'Total Units',
                icon: 'door-open',
                baseQuery: clone $unitQuery
            ),
            $this->metricService->buildCountMetric(
                title: 'Available Units',
                icon: 'key',
                baseQuery: (clone $unitQuery)->where('status', 'available')
            ),
            $this->metricService->buildCountMetric(
                title: 'Occupied Units',
                icon: 'home-modern',
                baseQuery: (clone $unitQuery)->where('status', 'occupied')
            ),
            $this->metricService->buildCountMetric(
                title: 'Under Maintenance',
                icon: 'wrench',
                baseQuery: (clone $unitQuery)->where('status', 'maintenance')
            ),
        ];

        return $this->success([
            'metrics' => $widgets
        ], 'Dashboard metrics retrieved successfully.');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $tenantId = $request->user()->tenant_business_id;
        $units = PropertyUnit::query()
            ->with(['property.tenantBusiness', 'property.amenities', 'amenities'])
            ->whereHas('property', function ($query) use ($tenantId) {
                $query->where('tenant_business_id', $tenantId);
            })
            ->latest()
            ->paginate($request->input('per_page', 15));

        return PropertyUnitData::collect($units, PaginatedDataCollection::class);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage. Supports creating a single
     * unit or multiple units (each with its own price/capacity) in one call.
     */
    public function store(StorePropertyUnitRequest $request): JsonResponse
    {
        $data = $request->validated();
        $propertyId = UuidResolver::id('properties', $data['property_uuid']);

        $units = $this->propertyUnitService->createMany($propertyId, $data['units']);

        foreach ($units as $unit) {
            $this->auditLogger->record(
                module: AuditModule::PROPERTY_UNIT,
                action: AuditAction::CREATED,
                description: "Created unit \"{$unit->name}\" for property ID {$unit->property_id}",
                auditable: $unit,
                newValues: $unit->getAttributes(),
            );
        }

        $payload = count($units) === 1 ? $units[0] : $units;

        return $this->success($payload, 'Unit(s) created successfully.', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(PropertyUnit $propertyUnit): JsonResponse
    {
        $this->authorize('view', $propertyUnit);
        return $this->success($propertyUnit);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePropertyUnitRequest $request, PropertyUnit $propertyUnit): JsonResponse
    {
        $originalValues = $propertyUnit->getOriginal();

        $propertyUnit->update($request->validated());

        if ($propertyUnit->wasChanged()) {
            $this->auditLogger->record(
                module: AuditModule::PROPERTY_UNIT,
                action: AuditAction::UPDATED,
                description: "Updated unit \"{$propertyUnit->name}\"",
                auditable: $propertyUnit,
                oldValues: $originalValues,
            );
        }

        return $this->success($propertyUnit, 'Unit updated successfully.');
    }

    /**
     * Sync the amenities assigned to this property unit.
     */
    public function syncAmenities(SyncAmenitiesRequest $request, PropertyUnit $propertyUnit): JsonResponse
    {
        abort_unless($propertyUnit->property?->tenant_business_id === $request->user()->tenant_business_id, 404);

        $amenityIds = UuidResolver::ids('amenities', $request->validated('amenity_uuids'));

        $propertyUnit->amenities()->sync($amenityIds);

        $this->auditLogger->record(
            module: AuditModule::PROPERTY_UNIT,
            action: AuditAction::UPDATED,
            description: "Synced amenities for unit \"{$propertyUnit->name}\"",
            auditable: $propertyUnit,
            newValues: ['amenity_ids' => $amenityIds],
        );

        return $this->success($propertyUnit->load('amenities'), 'Amenities updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PropertyUnit $propertyUnit): JsonResponse
    {
        $this->authorize('delete', $propertyUnit);

        $unitName = $propertyUnit->name;
        $originalValues = $propertyUnit->getAttributes();

        $propertyUnit->delete();

        $this->auditLogger->record(
            module: AuditModule::PROPERTY_UNIT,
            action: AuditAction::DELETED,
            description: "Deleted unit \"{$unitName}\"",
            oldValues: $originalValues,
        );

        return $this->success(null, 'Unit deleted successfully.');
    }
}