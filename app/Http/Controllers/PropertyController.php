<?php

namespace App\Http\Controllers;

use App\Data\Property\PropertyData;
use App\Data\PropertyAttachment\PropertyAttachmentData;
use App\Enum\AuditAction;
use App\Enum\AuditModule;
use App\Http\Requests\Property\StorePropertyAttachmentRequest;
use App\Http\Requests\Property\StorePropertyRequest;
use App\Http\Requests\Property\SyncAmenitiesRequest;
use App\Http\Requests\Property\UpdatePropertyRequest;
use App\Models\Property;
use App\Models\PropertyAttachment;
use App\Models\PropertyUnit;
use App\Services\AuditLog\AuditLogger;
use App\Services\DashboardMetricService;
use App\Services\PropertyAttachment\PropertyAttachmentService;
use App\Support\UuidResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\LaravelData\PaginatedDataCollection;

class PropertyController extends Controller
{
    public function __construct(
        protected AuditLogger $auditLogger,
        protected DashboardMetricService $metricService,
        protected PropertyAttachmentService $propertyAttachmentService,
    ) {}

    public function dashboard(Request $request): JsonResponse
    {
        // Property and PropertyUnit are both tenant-scoped by their own global
        // scope (and left unscoped for super admins), so no manual filter here.
        $propertyQuery = Property::query();
        $unitQuery = PropertyUnit::query();

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
            'metrics' => $widgets,
        ], 'Dashboard metrics retrieved successfully.');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $tenantBusinessId = $request->user()->tenant_business_id;
        $properties = Property::query()
            ->with(['amenities', 'attachments'])
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

        return $this->success(PropertyData::from($property), 'Property created successfully.', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Property $property): JsonResponse
    {
        return $this->success(PropertyData::from($property->load(['amenities', 'attachments'])));
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

        return $this->success(PropertyData::from($property), 'Property updated successfully.');
    }

    /**
     * Sync the amenities assigned to this property.
     */
    public function syncAmenities(SyncAmenitiesRequest $request, Property $property): JsonResponse
    {
        $amenityIds = UuidResolver::ids('amenities', $request->validated('amenity_uuids'));

        $property->amenities()->sync($amenityIds);

        $this->auditLogger->record(
            module: AuditModule::PROPERTY,
            action: AuditAction::UPDATED,
            description: "Synced amenities for property \"{$property->name}\"",
            auditable: $property,
            newValues: ['amenity_ids' => $amenityIds],
        );

        return $this->success(PropertyData::from($property->load('amenities')), 'Amenities updated successfully.');
    }

    /**
     * Upload one or more images for this property.
     */
    public function storeAttachments(StorePropertyAttachmentRequest $request, Property $property): JsonResponse
    {
        $attachments = $this->propertyAttachmentService->attach(
            $property,
            $request->file('images'),
            $request->input('captions', []),
        );

        $this->auditLogger->record(
            module: AuditModule::PROPERTY,
            action: AuditAction::UPDATED,
            description: 'Added '.count($attachments)." image(s) to property \"{$property->name}\"",
            auditable: $property,
            newValues: ['attachment_ids' => array_map(fn ($attachment) => $attachment->id, $attachments)],
        );

        return $this->success(PropertyAttachmentData::collect($attachments), 'Image(s) uploaded successfully.', 201);
    }

    /**
     * Remove an uploaded image from this property.
     */
    public function destroyAttachment(Property $property, PropertyAttachment $attachment): JsonResponse
    {
        abort_unless(
            $attachment->attachable_type === $property->getMorphClass() && $attachment->attachable_id === $property->id,
            404
        );

        $this->propertyAttachmentService->delete($attachment);

        $this->auditLogger->record(
            module: AuditModule::PROPERTY,
            action: AuditAction::UPDATED,
            description: "Removed image from property \"{$property->name}\"",
            auditable: $property,
        );

        return $this->success(null, 'Image deleted successfully.');
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
            auditable: $property,
            oldValues: $originalValues,
        );

        return $this->success(null, 'Property deleted successfully.');
    }
}
