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
                'first_name' => 'SaaS',
                'last_name' => 'Owner',
                'email' => 'superadmin@app.com',
                'username' => 'superadmin',
                'password' => $defaultPassword,
                'email_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // BUSINESS A: Dela Cruz Apartments
            [
                'role_id' => $adminRoleId,
                'tenant_business_id' => $businessAId,
                'first_name' => 'Juan',
                'last_name' => 'Dela Cruz',
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
                'first_name' => 'Mark',
                'last_name' => 'Santos (Day Shift)',
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
                'first_name' => 'Maria',
                'last_name' => 'Clara (Night Shift)',
                'username' => 'mariaclara',
                'email' => 'maria.staff@delacruzrentals.ph',
                'password' => $defaultPassword,
                'email_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // BUSINESS B: Sampaloc University Belt Dorms
            [
                'role_id' => $adminRoleId,
                'tenant_business_id' => $businessBId,
                'first_name' => 'Maria',
                'last_name' => 'Santos',
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
                'first_name' => 'Jose',
                'last_name' => 'Rizal (Dorm Caretaker)',
                'username' => 'joserizal',
                'email' => 'jose.staff@ubelt-dorms.com',
                'password' => $defaultPassword,
                'email_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // BUSINESS C: Metro Share Condominiums
            [
                'role_id' => $adminRoleId,
                'tenant_business_id' => $businessCId,
                'first_name' => 'Renato',
                'last_name' => 'Luna',
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
                'first_name' => 'Pedro',
                'last_name' => 'Penduko (Leasing Officer)',
                'username' => 'pedropenduko',
                'email' => 'pedro.staff@metrosharecondos.ph',
                'password' => $defaultPassword,
                'email_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // BUSINESS D: Cebu Highlands Housing Corp
            [
                'role_id' => $adminRoleId,
                'tenant_business_id' => $businessDId,
                'first_name' => 'Christina',
                'last_name' => 'Garcia',
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
                'first_name' => 'Niño',
                'last_name' => 'Cebuano (Collector)',
                'username' => 'ninocebuano',
                'email' => 'nino.staff@cebuhighlands.com',
                'password' => $defaultPassword,
                'email_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // BUSINESS E: Apex Property Management Group
            [
                'role_id' => $adminRoleId,
                'tenant_business_id' => $businessEId,
                'first_name' => 'Alejandro',
                'last_name' => 'Valdez',
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
                'first_name' => 'Elena',
                'last_name' => 'Cruz (Desk Staff)',
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