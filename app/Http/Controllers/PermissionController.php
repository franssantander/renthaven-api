<?php

namespace App\Http\Controllers;

use App\Data\Permission\PermissionModuleData;
use App\Http\Requests\Permission\SyncUserPermissionRequest;
use App\Http\Requests\RevokeAllPermissionsRequest;
use App\Models\PermissionModule;
use App\Models\User;
use App\Services\Permission\PermissionService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionController extends Controller
{
    public function __construct(
        protected PermissionService $permissionService,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $modules = PermissionModule::with('actions')->get();
        $options = PermissionModuleData::collect($modules);

        return $this->success($options);
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
    public function store(Request $request) {}

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        $authUser = auth()->user();
        if ($authUser->role->slug !== 'super_admin' && $authUser->tenant_business_id !== $user->tenant_business_id) {
            return $this->error(null, 'Unauthorized to view this user\'s permissions.', Response::HTTP_FORBIDDEN);
        }

        $matrix = $this->permissionService->getUserPermissionMatrix($user);

        return $this->success($matrix);
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
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function sync(SyncUserPermissionRequest $request)
    {
        $targetUuid = $request->validated('user_id');
        $this->assertCanManagePermissionsFor($targetUuid);

        $this->permissionService->syncUserPermissions($targetUuid, $request->input('permissions', []));

        return $this->success(null, 'User permissions synchronized successfully.');
    }

    public function revokeAll(RevokeAllPermissionsRequest $request)
    {
        $targetUuid = $request->validated('user_id');
        $this->assertCanManagePermissionsFor($targetUuid);

        $this->permissionService->revokeAllUserPermissions($targetUuid);

        return $this->success(null, 'All custom user permissions have been successfully revoked.');
    }

    /**
     * A non-super-admin may only manage permission overrides for users
     * within their own tenant business.
     */
    protected function assertCanManagePermissionsFor(string $userUuid): void
    {
        $authUser = auth()->user();

        if ($authUser->role->slug === 'super_admin') {
            return;
        }

        $targetTenantId = User::withoutGlobalScopes()->where('uuid', $userUuid)->value('tenant_business_id');

        abort_if(
            $targetTenantId === null || $targetTenantId !== $authUser->tenant_business_id,
            Response::HTTP_NOT_FOUND
        );
    }
}
