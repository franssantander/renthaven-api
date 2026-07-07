<?php

namespace App\Http\Controllers;

use App\Actions\Permission\GetUserPermissionMatrixAction;
use App\Actions\Permission\RevokeAllUserPermissionsAction;
use App\Actions\Permission\SyncUserPermissionAction;
use App\Data\Permission\PermissionModuleData;
use App\Data\Permission\SyncUserPermissionsData;
use App\Http\Requests\Permission\SyncUserPermissionRequest;
use App\Http\Requests\RevokeAllPermissionsRequest;
use App\Models\PermissionAction;
use App\Models\PermissionModule;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class PermissionController extends Controller
{
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
    public function show(User $user, GetUserPermissionMatrixAction $action)
    {
        $authUser = auth()->user();
        if ($authUser->role->slug !== 'super_admin' && $authUser->tenant_business_id !== $user->tenant_business_id) {
            return $this->error(null, 'Unauthorized to view this user\'s permissions.',  Response::HTTP_FORBIDDEN);
        }

        $matrix = $action->handle($user);
        return response()->json([
            'data' => $matrix
        ]);
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

    public function sync(SyncUserPermissionRequest $request, SyncUserPermissionAction $action)
    {
        $action->handle($request->validated('user_id'), $request->input('permissions', []));
        return $this->success(null, 'User permissions synchronized successfully.');
    }

    public function revokeAll(RevokeAllPermissionsRequest $request, RevokeAllUserPermissionsAction $action)
    {
        $action->handle($request->validated('user_id'));
        return $this->success(null, 'All custom user permissions have been successfully revoked.');
    }
}