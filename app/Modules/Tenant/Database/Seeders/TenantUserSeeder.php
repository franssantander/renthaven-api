<?php

namespace Modules\Tenant\Database\Seeders;

use App\Modules\Authentication\Models\Role;
use App\Modules\Authentication\Models\User;
use App\Modules\Tenant\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenantUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $roles = Role::all();

        if ($users->isEmpty() || $roles->isEmpty()) {
            $this->command->error("Users or Roles are missing. Seed them first!");
            return;
        }

        foreach ($users as $user) {
            $tenantId = $user->tenant_id;

            if (!$tenantId) {
                $tenantId = Tenant::inRandomOrder()->first()->id;
            }

            if (str_starts_with($user->username, 'superadmin')) {
                $role = $roles->where('code', 'superadmin')->first();
            } elseif (str_starts_with($user->username, 'admin')) {
                $role = $roles->where('code', 'admin')->first();
            } else {
                $role = $roles->where('code', 'renter')->first();
            }

            $role = $role ?? $roles->first();

            DB::table('tenant_users')->updateOrInsert(
                [
                    'user_id' => $user->id,
                    'tenant_id' => $tenantId,
                ],
                [
                    'uuid' => (string) Str::uuid(),
                    'role_id' => $role->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $this->command->info('Tenant-User-Role relationships created!');
    }
}