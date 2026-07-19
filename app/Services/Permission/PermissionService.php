<?php

namespace App\Services\Permission;

use App\Models\User;
use App\Support\UuidResolver;
use Illuminate\Support\Facades\DB;

class PermissionService
{
    /**
     * Get only the active assigned permissions for a user.
     */
    public function getUserPermissionMatrix(User $user): array
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

    /**
     * Synchronize a user's custom permission overrides.
     */
    public function syncUserPermissions(string $userUuid, array $permissions): void
    {
        $userId = UuidResolver::id('users', $userUuid);

        if (!$userId) {
            return;
        }

        $moduleUuids = collect($permissions)->pluck('module_uuid')->unique()->toArray();
        $actionUuids = collect($permissions)->pluck('action_uuid')->flatten()->unique()->toArray();

        // Map public UUIDs to internal IDs
        $moduleMap = DB::table('permission_modules')->whereIn('uuid', $moduleUuids)->pluck('id', 'uuid');
        $actionMap = DB::table('permission_actions')->whereIn('uuid', $actionUuids)->pluck('id', 'uuid');

        $now = now();
        $upsertData = [];

        foreach ($permissions as $item) {
            $moduleUuid = $item['module_uuid'] ?? null;
            $moduleId = $moduleMap[$moduleUuid] ?? null;

            if (!$moduleId || !isset($item['action_uuid'])) {
                continue;
            }

            foreach ($item['action_uuid'] as $actionUuid) {
                $actionId = $actionMap[$actionUuid] ?? null;
                if (!$actionId) {
                    continue;
                }

                $upsertData[] = [
                    'user_id'              => $userId,
                    'permission_module_id' => $moduleId,
                    'permission_action_id' => $actionId,
                    'is_active'            => true,
                    'created_at'           => $now,
                    'updated_at'           => $now,
                ];
            }
        }

        DB::transaction(function () use ($userId, $upsertData) {
            DB::table('permission_per_user')
                ->where('user_id', $userId)
                ->update([
                    'is_active'  => false,
                    'updated_at' => now(),
                ]);

            if (!empty($upsertData)) {
                DB::table('permission_per_user')->upsert(
                    $upsertData,
                    ['user_id', 'permission_module_id', 'permission_action_id'],
                    ['is_active', 'updated_at']
                );
            }
        });
    }

    /**
     * Revoke all custom permission overrides for a user.
     */
    public function revokeAllUserPermissions(string $userUuid): void
    {
        $userId = UuidResolver::id('users', $userUuid);

        if (!$userId) {
            return;
        }

        DB::table('permission_per_user')
            ->where('user_id', $userId)
            ->update([
                'is_active'  => false,
                'updated_at' => now(),
            ]);
    }
}
