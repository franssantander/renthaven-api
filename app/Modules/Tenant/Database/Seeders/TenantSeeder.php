<?php

namespace Modules\Tenant\Database\Seeders;

use App\Modules\Authentication\Models\User;
use App\Modules\RenterManagement\Models\Renter;
use App\Modules\Tenant\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;


class TenantSeeder extends Seeder
{

    public function run(): void
    {
        // 1. Create specific test tenants
        $this->createTenantWithAccounts('Alpha Properties', 'alpha.com');
        $this->createTenantWithAccounts('Beta Rentals', 'beta.com');

        // 2. Create 3 random Tenants
        Tenant::factory()->count(3)->create()->each(function ($tenant) {
            $domain = str_replace(' ', '', strtolower($tenant->name)) . ".com";
            $this->generateAccountsForTenant($tenant, $domain);
        });
    }

    private function createTenantWithAccounts($name, $domain)
    {
        $tenant = Tenant::where('name', $name)->first();

        if (!$tenant) {
            $tenant = Tenant::factory()->create([
                'name' => $name,
                'email' => "info@$domain",
                'url' => str_replace(' ', '', strtolower($name)) . ".propertymanager.test"
            ]);

            $this->generateAccountsForTenant($tenant, $domain);
        }
    }

    private function generateAccountsForTenant($tenant, $domain)
    {
        // 1. SUPERADMIN Account
        User::create([
            'tenant_id' => $tenant->id,
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'username' => 'superadmin_' . str_replace('.', '_', $domain),
            'email' => "superadmin@$domain",
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        // 2. ADMIN Account
        User::create([
            'tenant_id' => $tenant->id,
            'first_name' => 'General',
            'last_name' => 'Manager',
            'username' => 'admin_' . str_replace('.', '_', $domain),
            'email' => "admin@$domain",
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);


        Renter::create([
            'uuid' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'first_name' => 'John',
            'last_name' => 'Renter',
            'username' => 'renter_' . str_replace('.', '_', $domain),
            'email' => "renter@$domain",
            'phone_number' => '09123456789',
            'is_active' => true,
        ]);
    }
}