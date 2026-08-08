<?php

namespace App\Http\Controllers;

use App\Data\PropertyAttachment\PropertyAttachmentData;
use App\Data\PropertyUnit\PropertyUnitData;
use App\Enum\AuditAction;
use App\Enum\AuditModule;
use App\Http\Requests\PropertyUnit\ReorderPropertyAttachmentsRequest;
use App\Http\Requests\PropertyUnit\StorePropertyAttachmentRequest;
use App\Http\Requests\PropertyUnit\StorePropertyUnitRequest;
use App\Http\Requests\PropertyUnit\SyncAmenitiesRequest;
use App\Http\Requests\PropertyUnit\UpdatePropertyUnitRequest;
use App\Models\PropertyAttachment;
use App\Models\PropertyUnit;
use App\Services\AuditLog\AuditLogger;
use App\Services\DashboardMetricService;
use App\Services\PropertyAttachment\PropertyAttachmentService;
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
        protected PropertyAttachmentService $propertyAttachmentService,
    ) {}

    /**
     * Display dashboard metrics for the tenant's property units.
     */
    public function dashboard(Request $request): JsonResponse
    {
        // PropertyUnit is tenant-scoped by its own global scope (and left
        // unscoped for super admins), so no manual tenant filter here.
        $unitQuery = PropertyUnit::query();

        if ($request->filled('property_uuid')) {
            $propertyId = UuidResolver::id('properties', $request->input('property_uuid'));
            $unitQuery->where('property_id', $propertyId);
        }

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
            'metrics' => $widgets,
        ], 'Dashboard metrics retrieved successfully.');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $propertyId = $request->filled('property_uuid')
            ? UuidResolver::id('properties', $request->input('property_uuid'))
            : null;

        $units = PropertyUnit::query()
            ->with(['property.tenantBusiness', 'property.amenities', 'property.attachments', 'amenities', 'attachments', 'activeLeases.renter'])
            ->when($propertyId, fn($query) => $query->where('property_id', $propertyId))
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
            $unit->load('amenities');

            $this->auditLogger->record(
                module: AuditModule::PROPERTY_UNIT,
                action: AuditAction::CREATED,
                description: "Created unit \"{$unit->name}\" for property ID {$unit->property_id}",
                auditable: $unit,
                newValues: $unit->getAttributes(),
            );
        }

        $payload = PropertyUnitData::collect($units);

        return $this->success(count($units) === 1 ? $payload[0] : $payload, 'Unit(s) created successfully.', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(PropertyUnit $propertyUnit): JsonResponse
    {
        return $this->success(PropertyUnitData::from($propertyUnit->load(['property.tenantBusiness', 'property.amenities', 'property.attachments', 'amenities', 'attachments', 'activeLeases.renter'])));
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

        $data = $request->validated();
        $amenityUuids = $data['amenity_uuids'] ?? null;
        unset($data['amenity_uuids']);

        $propertyUnit->update($data);

        if ($amenityUuids !== null) {
            $propertyUnit->amenities()->sync(UuidResolver::ids('amenities', $amenityUuids));
        }

        if ($propertyUnit->wasChanged()) {
            $this->auditLogger->record(
                module: AuditModule::PROPERTY_UNIT,
                action: AuditAction::UPDATED,
                description: "Updated unit \"{$propertyUnit->name}\"",
                auditable: $propertyUnit,
                oldValues: $originalValues,
            );
        }

        return $this->success(PropertyUnitData::from($propertyUnit->load(['amenities', 'attachments'])), 'Unit updated successfully.');
    }

    /**
     * Sync the amenities assigned to this property unit.
     */
    public function syncAmenities(SyncAmenitiesRequest $request, PropertyUnit $propertyUnit): JsonResponse
    {
        $amenityIds = UuidResolver::ids('amenities', $request->validated('amenity_uuids'));

        $propertyUnit->amenities()->sync($amenityIds);

        $this->auditLogger->record(
            module: AuditModule::PROPERTY_UNIT,
            action: AuditAction::UPDATED,
            description: "Synced amenities for unit \"{$propertyUnit->name}\"",
            auditable: $propertyUnit,
            newValues: ['amenity_ids' => $amenityIds],
        );

        return $this->success(PropertyUnitData::from($propertyUnit->load(['amenities', 'attachments'])), 'Amenities updated successfully.');
    }

    /**
     * Upload one or more images for this property unit.
     */
    public function storeAttachments(StorePropertyAttachmentRequest $request, PropertyUnit $propertyUnit): JsonResponse
    {
        $attachments = $this->propertyAttachmentService->attach(
            $propertyUnit,
            $request->file('images'),
            $request->input('captions', []),
        );

        $this->auditLogger->record(
            module: AuditModule::PROPERTY_UNIT,
            action: AuditAction::UPDATED,
            description: 'Added ' . count($attachments) . " image(s) to unit \"{$propertyUnit->name}\"",
            auditable: $propertyUnit,
            newValues: ['attachment_ids' => array_map(fn($attachment) => $attachment->id, $attachments)],
        );

        return $this->success(PropertyAttachmentData::collect($attachments), 'Image(s) uploaded successfully.', 201);
    }

    /**
     * Reorder the uploaded images for this property unit. The first uuid in
     * the given order becomes the lowest sort_order, i.e. the cover/profile
     * image shown on the unit list.
     */
    public function reorderAttachments(ReorderPropertyAttachmentsRequest $request, PropertyUnit $propertyUnit): JsonResponse
    {
        $attachmentUuids = $request->validated('attachment_uuids');
        $ownedUuids = $propertyUnit->attachments()->pluck('uuid')->all();

        abort_unless(
            count($attachmentUuids) === count($ownedUuids) && empty(array_diff($attachmentUuids, $ownedUuids)),
            422,
            "The provided photos do not match this unit's current photos."
        );

        $this->propertyAttachmentService->reorder($propertyUnit, $attachmentUuids);

        $this->auditLogger->record(
            module: AuditModule::PROPERTY_UNIT,
            action: AuditAction::UPDATED,
            description: "Reordered photos for unit \"{$propertyUnit->name}\"",
            auditable: $propertyUnit,
            newValues: ['attachment_order' => $attachmentUuids],
        );

        return $this->success(PropertyUnitData::from($propertyUnit->load(['amenities', 'attachments'])), 'Photo order updated successfully.');
    }

    /**
     * Remove an uploaded image from this property unit.
     */
    public function destroyAttachment(PropertyUnit $propertyUnit, PropertyAttachment $attachment): JsonResponse
    {
        abort_unless(
            $attachment->attachable_type === $propertyUnit->getMorphClass() && $attachment->attachable_id === $propertyUnit->id,
            404
        );

        $this->propertyAttachmentService->delete($attachment);

        $this->auditLogger->record(
            module: AuditModule::PROPERTY_UNIT,
            action: AuditAction::UPDATED,
            description: "Removed image from unit \"{$propertyUnit->name}\"",
            auditable: $propertyUnit,
        );

        return $this->success(null, 'Image deleted successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PropertyUnit $propertyUnit): JsonResponse
    {
        $unitName = $propertyUnit->name;
        $originalValues = $propertyUnit->getAttributes();

        $propertyUnit->delete();

        $this->auditLogger->record(
            module: AuditModule::PROPERTY_UNIT,
            action: AuditAction::DELETED,
            description: "Deleted unit \"{$unitName}\"",
            auditable: $propertyUnit,
            oldValues: $originalValues,
        );

        return $this->success(null, 'Unit deleted successfully.');
    }
}