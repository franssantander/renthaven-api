<?php

namespace App\Http\Controllers;

use App\Data\Property\PropertyData;
use App\Enum\AuditAction;
use App\Enum\AuditModule;
use App\Http\Requests\Property\StorePropertyRequest;
use App\Http\Requests\Property\SyncAmenitiesRequest;
use App\Http\Requests\Property\UpdatePropertyRequest;
use App\Models\Property;
use App\Models\PropertyUnit;
use App\Services\AuditLog\AuditLogger;
use App\Services\DashboardMetricService;
use App\Support\UuidResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\LaravelData\PaginatedDataCollection;
 
class PropertyController extends Controller
{

    public function __construct(
        protected AuditLogger $auditLogger,
        protected DashboardMetricService $metricService
    ) {}

    public function dashboard(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_business_id;
        $propertyQuery = Property::query()->where('tenant_business_id', $tenantId);

        $unitQuery = PropertyUnit::query()->whereHas('property', function ($query) use ($tenantId) {
            $query->where('tenant_business_id', $tenantId);
        });


        $widgets = [
            $this->metricService->buildCountMetric(
                title: 'Total Properties',
                icon: 'building-office',
                baseQuery: $propertyQuery
            ),
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
        $tenantBusinessId = $request->user()->tenant_business_id;
        $properties = Property::query()
            ->with('amenities')
            ->where('tenant_business_id', $tenantBusinessId)
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return PropertyData::collect($properties, PaginatedDataCollection::class);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePropertyRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['tenant_business_id'] = UuidResolver::id('tenant_businesses', $data['tenant_business_uuid']);
        unset($data['tenant_business_uuid']);

        $property = Property::create($data);

        $this->auditLogger->record(
            module: AuditModule::PROPERTY,
            action: AuditAction::CREATED,
            description: "Created property \"{$property->name}\"",
            auditable: $property,
            newValues: $property->getAttributes(),
        );

        return $this->success($property, 'Property created successfully.', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Property $property): JsonResponse
    {
        return $this->success($property);
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
    public function update(UpdatePropertyRequest $request, Property $property): JsonResponse
    {
        $originalValues = $property->getOriginal();

        $property->update($request->validated());

        if ($property->wasChanged()) {
            $this->auditLogger->record(
                module: AuditModule::PROPERTY,
                action: AuditAction::UPDATED,
                description: "Updated property \"{$property->name}\"",
                auditable: $property,
                oldValues: $originalValues,
            );
        }

        return $this->success($property, 'Property updated successfully.');
    }
    /**
     * Sync the amenities assigned to this property.
     */
    public function syncAmenities(SyncAmenitiesRequest $request, Property $property): JsonResponse
    {
        abort_unless($property->tenant_business_id === $request->user()->tenant_business_id, 404);

        $amenityIds = UuidResolver::ids('amenities', $request->validated('amenity_uuids'));

        $property->amenities()->sync($amenityIds);

        $this->auditLogger->record(
            module: AuditModule::PROPERTY,
            action: AuditAction::UPDATED,
            description: "Synced amenities for property \"{$property->name}\"",
            auditable: $property,
            newValues: ['amenity_ids' => $amenityIds],
        );

        return $this->success($property->load('amenities'), 'Amenities updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Property $property): JsonResponse
    {
        $propertyName = $property->name;
        $originalValues = $property->getAttributes();

        $property->delete();

        $this->auditLogger->record(
            module: AuditModule::PROPERTY,
            action: AuditAction::DELETED,
            description: "Deleted property \"{$propertyName}\"",
            oldValues: $originalValues,
        );

        return $this->success(null, 'Property deleted successfully.');
    }
}