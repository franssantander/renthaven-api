<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdminId = DB::table('roles')->where('slug', 'super_admin')->value('id');
        $sysAdminRoleId = DB::table('roles')->where('slug', 'system_admin')->value('id');
        $adminRoleId = DB::table('roles')->where('slug', 'admin')->value('id');
        $staffRoleId = DB::table('roles')->where('slug', 'staff')->value('id');

        $businessAId = DB::table('tenant_businesses')->where('name', 'Dela Cruz Apartments')->value('id');
        $businessBId = DB::table('tenant_businesses')->where('name', 'Sampaloc University Belt Dorms')->value('id');
        $businessCId = DB::table('tenant_businesses')->where('name', 'Metro Share Condominiums')->value('id');
        $businessDId = DB::table('tenant_businesses')->where('name', 'Cebu Highlands Housing Corp')->value('id');
        $businessEId = DB::table('tenant_businesses')->where('name', 'Apex Property Management Group')->value('id');

        $defaultPassword = Hash::make('password');
        $now = Carbon::now();

        $users = [
            [
                'role_id' => $superAdminId,
                'tenant_business_id' => null,
                'full_name' => 'SaaS Owner',
                'email' => 'superadmin@app.com',
                'username' => 'superadmin',
                'password' => $defaultPassword,
                'email_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // BUSINESS A: Dela Cruz Apartments
            [
                'role_id' => $sysAdminRoleId,
                'tenant_business_id' => $businessAId,
                'full_name' => 'Dela Cruz IT Support',
                'email' => 'sysadmin@delacruzrentals.ph',
                'username' => 'delacruztech',
                'password' => $defaultPassword,
                'email_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'role_id' => $adminRoleId,
                'tenant_business_id' => $businessAId,
                'full_name' => 'Juan Dela Cruz',
                'username' => 'juandelacruz',
                'email' => 'juan.admin@delacruzrentals.ph',
                'password' => $defaultPassword,
                'email_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'role_id' => $staffRoleId,
                'tenant_business_id' => $businessAId,
                'full_name' => 'Mark Santos (Day Shift)',
                'username' => 'marksantos',
                'email' => 'mark.staff@delacruzrentals.ph',
                'password' => $defaultPassword,
                'email_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'role_id' => $staffRoleId,
                'tenant_business_id' => $businessAId,
                'full_name' => 'Maria Clara (Night Shift)',
                'username' => 'mariaclara',
                'email' => 'maria.staff@delacruzrentals.ph',
                'password' => $defaultPassword,
                'email_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // BUSINESS B: Sampaloc University Belt Dorms
            [
                'role_id' => $sysAdminRoleId,
                'tenant_business_id' => $businessBId,
                'full_name' => 'U-Belt Dorms IT SysAdmin',
                'email' => 'it@ubelt-dorms.com',
                'username' => 'ubelttech',
                'password' => $defaultPassword,
                'email_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'role_id' => $adminRoleId,
                'tenant_business_id' => $businessBId,
                'full_name' => 'Maria Santos',
                'username' => 'mariasantos',
                'email' => 'maria.admin@ubelt-dorms.com',
                'password' => $defaultPassword,
                'email_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'role_id' => $staffRoleId,
                'tenant_business_id' => $businessBId,
                'full_name' => 'Jose Rizal (Dorm Caretaker)',
                'username' => 'joserizal',
                'email' => 'jose.staff@ubelt-dorms.com',
                'password' => $defaultPassword,
                'email_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // BUSINESS C: Metro Share Condominiums
            [
                'role_id' => $sysAdminRoleId,
                'tenant_business_id' => $businessCId,
                'full_name' => 'Metro Share Network Admin',
                'email' => 'sysadmin@metrosharecondos.ph',
                'username' => 'metrosharetech',
                'password' => $defaultPassword,
                'email_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'role_id' => $adminRoleId,
                'tenant_business_id' => $businessCId,
                'full_name' => 'Engr. Renato Luna',
                'username' => 'renatoluna',
                'email' => 'renato.admin@metrosharecondos.ph',
                'password' => $defaultPassword,
                'email_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'role_id' => $staffRoleId,
                'tenant_business_id' => $businessCId,
                'full_name' => 'Pedro Penduko (Leasing Officer)',
                'username' => 'pedropenduko',
                'email' => 'pedro.staff@metrosharecondos.ph',
                'password' => $defaultPassword,
                'email_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // BUSINESS D: Cebu Highlands Housing Corp
            [
                'role_id' => $sysAdminRoleId,
                'tenant_business_id' => $businessDId,
                'full_name' => 'Cebu Highlands IT Dept',
                'email' => 'it.support@cebuhighlands.com',
                'username' => 'cebuhighlandstech',
                'password' => $defaultPassword,
                'email_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'role_id' => $adminRoleId,
                'tenant_business_id' => $businessDId,
                'full_name' => 'Christina Garcia',
                'username' => 'christinagarcia',
                'email' => 'christina.admin@cebuhighlands.com',
                'password' => $defaultPassword,
                'email_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'role_id' => $staffRoleId,
                'tenant_business_id' => $businessDId,
                'full_name' => 'Niño Cebuano (Collector)',
                'username' => 'ninocebuano',
                'email' => 'nino.staff@cebuhighlands.com',
                'password' => $defaultPassword,
                'email_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // BUSINESS E: Apex Property Management Group
            [
                'role_id' => $sysAdminRoleId,
                'tenant_business_id' => $businessEId,
                'full_name' => 'Apex Systems Administrator',
                'email' => 'noc@apexproperties.ph',
                'username' => 'apexpropertiestech',
                'password' => $defaultPassword,
                'email_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'role_id' => $adminRoleId,
                'tenant_business_id' => $businessEId,
                'full_name' => 'Director Alejandro Valdez',
                'username' => 'alejandrovaldez',
                'email' => 'alejandro.admin@apexproperties.ph',
                'password' => $defaultPassword,
                'email_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'role_id' => $staffRoleId,
                'tenant_business_id' => $businessEId,
                'full_name' => 'Elena Cruz (Desk Staff)',
                'username' => 'elenacruz',
                'email' => 'elena.staff@apexproperties.ph',
                'password' => $defaultPassword,
                'email_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($users as $user) {
            DB::table('users')->updateOrInsert(
                ['email' => $user['email']],
                $user
            );
        }
    }
}