<?php

namespace App\Http\Controllers;

use App\Enum\AuditAction;
use App\Enum\AuditModule;
use App\Http\Requests\Property\StorePropertyRequest;
use App\Http\Requests\Property\UpdatePropertyRequest;
use App\Models\Property;
use App\Services\AuditLog\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PropertyController extends Controller
{

    public function __construct(
        protected AuditLogger $auditLogger
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
        $property = Property::create($request->validated());

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