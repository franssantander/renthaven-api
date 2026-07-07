<?php

namespace App\Actions\Permission;

use Illuminate\Support\Facades\DB;

class SyncUserPermissionAction
{
    /**
     * Execute the action.
     */
    public function handle(string $userUuid, array $permissions): void
    {
        $userId = DB::table('users')->where('uuid', $userUuid)->value('id');

        if (!$userId) {
            return;
        }

        $moduleUuids = collect($permissions)->pluck('module_uuid')->unique()->toArray();
        $actionUuids = collect($permissions)->pluck('action_uuid')->flatten()->unique()->toArray();

        // 3. Map public UUIDs to internal IDs
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
}