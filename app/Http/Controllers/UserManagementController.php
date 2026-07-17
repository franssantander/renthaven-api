<?php

namespace App\Http\Controllers;

use App\Data\UserData;
use App\Enum\AuditAction;
use App\Enum\AuditModule;
use App\Http\Requests\UserManagement\StoreUserManagementRequest;
use App\Http\Requests\UserManagement\UpdateUserManagementRequest;
use App\Models\User;
use App\Services\AuditLog\AuditLogger;
use App\Support\UuidResolver;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class UserManagementController extends Controller
{

    public function __construct(protected AuditLogger $auditLogger) {}

    /**
     * Display a listing of the resource scoped by tenant.
     */
    public function index(Request $request)
    {
        $authUser = auth()->user();
        $query = User::query()->with(['role', 'tenantBusiness']);

        if ($authUser->role->slug !== 'super_admin') {
            $query->where('tenant_business_id', $authUser->tenant_business_id);
        }

        $users = $query->latest()->paginate($request->input('per_page', 15));

        return UserData::collect($users);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserManagementRequest $request): JsonResponse
    {
        $authUser = auth()->user();
        $data = $request->validated();

        $data['role_id'] = UuidResolver::id('roles', $data['role_uuid']);
        unset($data['role_uuid']);

        if ($authUser->role->slug !== 'super_admin') {
            $data['tenant_business_id'] = $authUser->tenant_business_id;
        } else {
            $data['tenant_business_id'] = UuidResolver::id('tenant_businesses', $data['tenant_business_uuid']);
            unset($data['tenant_business_uuid']);
        }

        $data['password'] = Hash::make($data['password']);
        $newUser = User::create($data);

        $this->auditLogger->record(
            module: AuditModule::USER_MANAGEMENT,
            action: AuditAction::CREATED,
            description: "Created user account for {$newUser->email}",
            auditable: $newUser,
            newValues: $newUser->getAttributes(),
        );


        return $this->success($newUser, 'User created successfully.', Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user): JsonResponse
    {
        $authUser = auth()->user();

        if ($authUser->role->slug !== 'super_admin' && $authUser->tenant_business_id !== $user->tenant_business_id) {
            return $this->error(null, 'Unauthorized to view this user.', Response::HTTP_FORBIDDEN);
        }

        $user->load(['role', 'tenantBusiness']);

        return $this->success(UserData::from($user), 'User retrieved successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserManagementRequest $request, User $user): JsonResponse
    {
        $authUser = auth()->user();

        // Prevent cross-tenant horizontal data manipulation
        if ($authUser->role->slug !== 'super_admin' && $authUser->tenant_business_id !== $user->tenant_business_id) {
            return $this->error(null, 'Unauthorized to update this user.', Response::HTTP_FORBIDDEN);
        }

        $data = $request->validated();

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        if (array_key_exists('role_uuid', $data)) {
            $data['role_id'] = UuidResolver::id('roles', $data['role_uuid']);
            unset($data['role_uuid']);
        }

        if ($authUser->role->slug !== 'super_admin') {
            unset($data['tenant_business_uuid']);
        } elseif (array_key_exists('tenant_business_uuid', $data)) {
            $data['tenant_business_id'] = UuidResolver::id('tenant_businesses', $data['tenant_business_uuid']);
            unset($data['tenant_business_uuid']);
        }
        $before = $user->getOriginal();

        $user->update($data);

        $this->auditLogger->record(
            module: AuditModule::USER_MANAGEMENT,
            action: AuditAction::UPDATED,
            description: "Updated user details for {$user->email}.",
            auditable: $user,
            oldValues: $before,
        );

        return $this->success($user, 'User updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user): JsonResponse
    {
        $authUser = auth()->user();

        if ($authUser->role->slug !== 'super_admin' && $authUser->tenant_business_id !== $user->tenant_business_id) {
            return $this->error(null, 'Unauthorized to delete this user.', Response::HTTP_FORBIDDEN);
        }

        if ($authUser->id === $user->id) {
            return $this->error(null, 'You cannot delete your own account.', Response::HTTP_BAD_REQUEST);
        }

        $user->delete();

        $this->auditLogger->record(
            module: AuditModule::USER_MANAGEMENT,
            action: AuditAction::DELETED,
            description: "Deleted user details for {$user->email}.",
            auditable: $user,
        );

        return $this->success(null, 'User deleted successfully.');
    }
}