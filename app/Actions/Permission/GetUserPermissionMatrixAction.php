<?php

namespace App\Actions\Permission;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class GetUserPermissionMatrixAction
{
    /**
     * Get only the active assigned permissions for a user.
     *
     * @param User $user
     * @return array
     */
    public function handle(User $user): array
    {
        // 1. If the user is a Super Admin, they inherently have access to every module and action
        if ($user->role?->slug === 'super_admin') {
            $modules = DB::table('permission_modules')->get();
            $actions = DB::table('permission_actions')->get();

            return $modules->map(function ($module) use ($actions) {
                return [
                    'id'          => $module->id,
                    'uuid'        => $module->uuid,
                    'name'        => $module->name,
                    'description' => $module->description,
                    'actions'     => $actions->map(function ($action) {
                        return [
                            'id'          => $action->id,
                            'uuid'        => $action->uuid,
                            'name'        => $action->name,
                            'description' => $action->description,
                        ];
                    })->values()->toArray()
                ];
            })->values()->toArray();
        }

        // 2. Check if the user has specific overrides inside 'permission_per_user'
        $hasCustomPermissions = DB::table('permission_per_user')
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();

        $targetTable = $hasCustomPermissions ? 'permission_per_user' : 'role_permissions';
        $foreignKey  = $hasCustomPermissions ? 'user_id' : 'role_id';
        $foreignId   = $hasCustomPermissions ? $user->id : $user->role_id;

        $assignedPermissions = DB::table($targetTable)
            ->where("{$targetTable}.{$foreignKey}", $foreignId)
            ->when($hasCustomPermissions, function ($query) use ($targetTable) {
                return $query->where("{$targetTable}.is_active", true);
            })
            ->join('permission_modules', 'permission_modules.id', '=', "{$targetTable}.permission_module_id")
            ->join('permission_actions', 'permission_actions.id', '=', "{$targetTable}.permission_action_id")
            ->select([
                'permission_modules.id as module_id',
                'permission_modules.uuid as module_uuid',
                'permission_modules.name as module_name',
                'permission_modules.description as module_description',
                'permission_actions.id as action_id',
                'permission_actions.uuid as action_uuid',
                'permission_actions.name as action_name',
                'permission_actions.description as action_description',
            ])
            ->get();

        return $assignedPermissions->groupBy('module_id')->map(function ($group) {
            $first = $group->first();

            return [
                'id'          => $first->module_id,
                'uuid'        => $first->module_uuid,
                'name'        => $first->module_name,
                'description' => $first->module_description,
                'actions'     => $group->map(function ($row) {
                    return [
                        'id'          => $row->action_id,
                        'uuid'        => $row->action_uuid,
                        'name'        => $row->action_name,
                        'description' => $row->action_description,
                    ];
                })->values()->toArray()
            ];
        })->values()->toArray();
    }
}