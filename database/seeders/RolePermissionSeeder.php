<?php

namespace Database\Seeders;

use App\Models\PermissionAction;
use App\Models\PermissionModule;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('role_permissions')->truncate();

        $modules = PermissionModule::pluck('id', 'slug')->toArray();
        $actions = PermissionAction::pluck('id', 'slug')->toArray();
        $presets = [
            'staff' => [
                'dashboard'         => ['view'],
                'ledger'            => ['view', 'export'],
                'payment_approvals' => ['view', 'create'],
                'properties'        => ['view', 'create', 'update'],
                'renter_tenants'    => ['view', 'create', 'update'],
                'user_management'   => []
            ],

            'admin' => [
                'dashboard'         => ['view'],
                'ledger'            => ['view', 'create', 'update', 'delete', 'export', 'restore'],
                'payment_approvals' => ['view', 'create', 'update', 'delete', 'approve', 'restore'],
                'properties'        => ['view', 'create', 'update', 'delete', 'restore'],
                'renter_tenants'    => ['view', 'create', 'update', 'delete', 'export', 'restore'],
                'user_management'   => ['view', 'create', 'update', 'delete'],
                'tenant_business'   => ['view', 'create', 'update', 'delete'],
            ],
        ];

        $insertData = [];

        foreach ($presets as $roleSlug => $roleModules) {
            $role = Role::where('slug', $roleSlug)->first();

            if (!$role) continue;

            foreach ($roleModules as $moduleSlug => $moduleActions) {
                if (!isset($modules[$moduleSlug])) continue;

                foreach ($moduleActions as $actionCode) {
                    if (!isset($actions[$actionCode])) continue;

                    $insertData[] = [
                        'role_id' => $role->id,
                        'permission_module_id' => $modules[$moduleSlug],
                        'permission_action_id' => $actions[$actionCode],
                    ];
                }
            }
        }

        $superAdmin = Role::where('slug', 'super_admin')->first();

        if ($superAdmin) {
            foreach ($modules as $moduleSlug => $moduleId) {
                foreach ($actions as $actionSlug => $actionId) {
                    $insertData[] = [
                        'role_id' => $superAdmin->id,
                        'permission_module_id' => $moduleId,
                        'permission_action_id' => $actionId,
                    ];
                }
            }
        }

        DB::table('role_permissions')->insert($insertData);
    }
}