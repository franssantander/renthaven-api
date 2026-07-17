<?php

namespace Database\Seeders;

use App\Models\PermissionModule;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermissionModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modules = [
            [
                'name' => 'Dashboard',
                'slug' => 'dashboard',
                'description' => 'Overview and key metrics for the rental system.',
            ],
            [
                'name' => 'Ledger',
                'slug' => 'ledger',
                'description' => 'Financial tracking, income, and expenses.',
            ],
            [
                'name' => 'Payment Approvals',
                'slug' => 'payment_approvals',
                'description' => 'Review and approve incoming tenant payments.',
            ],
            [
                'name' => 'Properties',
                'slug' => 'properties',
                'description' => 'Manage buildings, units, and property details.',
            ],
            [
                'name' => 'Renter Tenants',
                'slug' => 'renter_tenants',
                'description' => 'Manage tenant profiles, leases, and documents.',
            ],
            [
                'name' => 'User Management',
                'slug' => 'user_management',
                'description' => 'Manage system users, roles, and permissions.',
            ],
            [
                'name' => 'Permission Management',
                'slug' => 'permission_management',
                'description' => 'Manage system permission, assign, and revoke.',
            ],
            [
                'name' => 'Tenant Business',
                'slug' => 'tenant_business',
                'description' => 'Manage business profile, informations, and details.',
            ],
            [
                'name' => 'Maintenance',
                'slug' => 'maintenance',
                'description' => 'Track and resolve tenant-reported maintenance requests.',
            ],
        ];

        foreach ($modules as $module) {
            PermissionModule::updateOrCreate(
                ['slug' => $module['slug']],
                $module
            );
        }
    }
}