<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionPerUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modules = DB::table('permission_modules')->pluck('id', 'slug')->toArray();
        $actions = DB::table('permission_actions')->pluck('id', 'slug')->toArray();

        $userIds = DB::table('users')->whereIn('email', [
            'mark.staff@delacruzrentals.ph',
            'elena.staff@apexproperties.ph'
        ])->pluck('id', 'email')->toArray();

        $customUserPermissions = [
            'mark.staff@delacruzrentals.ph' => [
                'dashboard'         => ['view'],
                'properties'        => ['view', 'create', 'update'],
                'renter_tenants'    => ['view', 'create', 'update'],
                'payment_approvals' => ['view', 'approve'],
            ],

            'elena.staff@apexproperties.ph' => [
                'dashboard'      => ['view'],
                'properties'     => ['view'],
                'renter_tenants' => ['view'],
            ],
        ];

        $insertData = [];

        foreach ($customUserPermissions as $email => $userModules) {
            if (!isset($userIds[$email])) continue;
            $userId = $userIds[$email];

            foreach ($userModules as $moduleSlug => $actionSlugs) {
                if (!isset($modules[$moduleSlug])) continue;
                $moduleId = $modules[$moduleSlug];

                foreach ($actionSlugs as $actionSlug) {
                    if (!isset($actions[$actionSlug])) continue;
                    $actionId = $actions[$actionSlug];

                    $insertData[] = [
                        'user_id'              => $userId,
                        'permission_module_id' => $moduleId,
                        'permission_action_id' => $actionId,
                    ];
                }
            }
        }

        DB::table('permission_per_user')->truncate();

        if (!empty($insertData)) {
            DB::table('permission_per_user')->insert($insertData);
        }
    }
}