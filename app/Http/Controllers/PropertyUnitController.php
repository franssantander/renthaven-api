<?php

namespace App\Http\Controllers;

use App\Data\PropertyUnit\PropertyUnitData;
use App\Enum\AuditAction;
use App\Enum\AuditModule;
use App\Http\Requests\PropertyUnit\StorePropertyUnitRequest;
use App\Http\Requests\PropertyUnit\UpdatePropertyUnitRequest;
use App\Models\PropertyUnit;
use App\Services\AuditLog\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\LaravelData\PaginatedDataCollection;

class PropertyUnitController extends Controller
{

    public function __construct(protected AuditLogger $auditLogger) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_business_id;
        $units = PropertyUnit::query()
            ->whereHas('property', function ($query) use ($tenantId) {
                $query->where('tenant_business_id', $tenantId);
            })
            ->latest()
            ->paginate($request->input('per_page', 15));

        // return PropertyUnitData::collect($units, PaginatedDataCollection::class);
        return PaginatedDataCollection::class(PropertyUnitData::collect($units));
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
    public function store(StorePropertyUnitRequest $request): JsonResponse
    {
        $unit = PropertyUnit::create($request->validated());

        $this->auditLogger->record(
            module: AuditModule::PROPERTY_UNIT,
            action: AuditAction::CREATED,
            description: "Created unit \"{$unit->name}\" for property ID {$unit->property_id}",
            auditable: $unit,
            newValues: $unit->getAttributes(),
        );

        return $this->created($unit, 'Unit created successfully.', 201);
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