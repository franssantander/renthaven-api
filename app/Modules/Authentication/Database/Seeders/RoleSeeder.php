<?php

namespace Modules\Authentication\Database\Seeders;

use App\Modules\Authentication\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['code' => 'super_admin', 'name' => 'Super Admin'],
            ['code' => 'admin', 'name' => 'Admin'],
            ['code' => 'staff', 'name' => 'Staff'],
            ['code' => 'tenant', 'name' => 'Tenant']
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['code' => $role['code']],
                ['name' => $role['name']]
            );
        }
    }
}
