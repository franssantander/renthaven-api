<?php

namespace App\Http\Controllers;

use App\Enum\AuditAction;
use App\Enum\AuditModule;
use App\Enum\Role;
use App\Http\Requests\Amenity\StoreAmenityRequest;
use App\Http\Requests\Amenity\UpdateAmenityRequest;
use App\Models\Amenity;
use App\Services\AuditLog\AuditLogger;
use App\Services\Amenity\AmenityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AmenityController extends Controller
{
    public function __construct(
        protected AuditLogger $auditLogger,
        protected AmenityService $amenityService,
    ) {}

    /**
     * Display a listing of the resource: the global catalog plus the
     * caller's own tenant-custom amenity tags, if any.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantBusinessId = $request->user()?->tenant_business_id;

        $amenities = Amenity::query()
            ->where(function ($query) use ($tenantBusinessId) {
                $query->whereNull('tenant_business_id');

                if ($tenantBusinessId) {
                    $query->orWhere('tenant_business_id', $tenantBusinessId);
                }
            })
            ->when($request->filled('category'), function ($query) use ($request) {
                $query->where('category', $request->input('category'));
            })
            ->orderBy('name')
            ->get();

        return $this->success($amenities);
    }

    /**
     * Store a newly created resource in storage. Supports creating a single
     * custom amenity tag or several in one call. Super admins create global
     * catalog entries; tenant admins create tags scoped to their own business.
     */
    public function store(StoreAmenityRequest $request): JsonResponse
    {
        $user = $request->user();
        $tenantBusinessId = $user->role->slug === Role::SUPER_ADMIN->value
            ? null
            : $user->tenant_business_id;

        $amenities = $this->amenityService->createMany($request->validated('amenities'), $tenantBusinessId);

        foreach ($amenities as $amenity) {
            $this->auditLogger->record(
                module: AuditModule::AMENITY,
                action: AuditAction::CREATED,
                description: "Created amenity \"{$amenity->name}\"",
                auditable: $amenity,
                newValues: $amenity->getAttributes(),
            );
        }

        $payload = count($amenities) === 1 ? $amenities[0] : $amenities;

        return $this->success($payload, 'Amenity(s) created successfully.', 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAmenityRequest $request, Amenity $amenity): JsonResponse
    {
        $this->authorizeOwnership($request, $amenity);

        $originalValues = $amenity->getOriginal();

        $amenity->update($request->validated());

        if ($amenity->wasChanged()) {
            $this->auditLogger->record(
                module: AuditModule::AMENITY,
                action: AuditAction::UPDATED,
                description: "Updated amenity \"{$amenity->name}\"",
                auditable: $amenity,
                oldValues: $originalValues,
            );
        }

        return $this->success($amenity, 'Amenity updated successfully.');
    }

    /**
     * Remove the specified resource from storage (soft delete only).
     */
    public function destroy(Request $request, Amenity $amenity): JsonResponse
    {
        $this->authorizeOwnership($request, $amenity);

        $amenityName = $amenity->name;
        $originalValues = $amenity->getAttributes();

        $amenity->delete();

        $this->auditLogger->record(
            module: AuditModule::AMENITY,
            action: AuditAction::DELETED,
            description: "Deleted amenity \"{$amenityName}\"",
            oldValues: $originalValues,
        );

        return $this->success(null, 'Amenity deleted successfully.');
    }

    /**
     * A super admin may modify any global (tenant_business_id null) amenity.
     * A tenant admin may modify only their own tenant's custom amenities.
     */
    protected function authorizeOwnership(Request $request, Amenity $amenity): void
    {
        $user = $request->user();

        if ($user->role->slug === Role::SUPER_ADMIN->value) {
            abort_unless($amenity->tenant_business_id === null, 403, 'Only global amenities can be managed by a super admin.');
            return;
        }

        abort_unless($amenity->tenant_business_id === $user->tenant_business_id, 403, 'You can only manage your own business\'s amenities.');
    }
}
