<?php

namespace App\Actions\Permission;

use Illuminate\Support\Facades\DB;

class RevokeAllUserPermissionsAction
{
    /**
     * Execute the action.
     */
    public function handle(string $userUuid): void
    {
        $userId = DB::table('users')->where('uuid', $userUuid)->value('id');

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