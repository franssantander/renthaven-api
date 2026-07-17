<?php

namespace Database\Seeders;

use App\Models\PermissionAction;
use App\Models\PermissionModule;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionModuleActionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $modules = PermissionModule::pluck('id', 'slug')->toArray();
        $actions = PermissionAction::pluck('id', 'slug')->toArray();

        $blueprint = [
            'dashboard' => [
                'view'
            ],
            'ledger' => [
                'view',
                'create',
                'update',
                'delete',
                'export',
                'restore'
            ],
            'payment_approvals' => [
                'view',
                'create',
                'update',
                'delete',
                'approve',
                'restore'
            ],
            'properties' => [
                'view',
                'create',
                'update',
                'delete',
                'restore'
            ],
            'renter_tenants' => [
                'view',
                'create',
                'update',
                'delete',
                'export',
                'restore'
            ],
            'user_management' => [
                'view',
                'create',
                'update',
                'delete',
                'restore'
            ],
            'maintenance' => [
                'view',
                'create',
                'update',
                'delete',
                'restore'
            ],
        ];

        $insertData = [];

        foreach ($blueprint as $moduleSlug => $actionCodes) {
            if (!isset($modules[$moduleSlug])) continue;

            foreach ($actionCodes as $actionCode) {
                if (!isset($actions[$actionCode])) continue;

                $insertData[] = [
                    'permission_module_id' => $modules[$moduleSlug],
                    'permission_action_id' => $actions[$actionCode],
                ];
            }
        }

        DB::table('permission_module_action')->truncate();
        DB::table('permission_module_action')->insert($insertData);
    }
}