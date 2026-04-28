<?php

namespace Modules\Tenant\Database\Seeders;

use App\Modules\Authentication\Models\User;
use App\Modules\Tenant\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->createTenantWithAdmin('Alpha Properties', 'admin@alpha.com');
        $this->createTenantWithAdmin('Beta Rentals', 'admin@beta.com');

        Tenant::factory()->count(5)->create()->each(function ($tenant) {
            User::factory()->create([
                'tenant_id' => $tenant->id,
                'first_name' => 'Admin',
                'last_name' => $tenant->name,
                'email' => "admin@" . str_replace(' ', '', strtolower($tenant->name)) . ".com",
            ]);
        });
    }

    /**
     * Helper function to create a tenant and its admin user at once
     */
    private function createTenantWithAdmin($name, $email)
    {
        $tenant = Tenant::factory()->create([
            'name' => $name,
            'email' => $email,
            'url' => str_replace(' ', '', strtolower($name)) . ".propertymanager.test"
        ]);

        User::create([
            'tenant_id' => $tenant->id,
            'first_name' => 'Owner',
            'last_name' => $name,
            'username' => str_replace(' ', '', strtolower($name)) . '_admin',
            'email' => $email,
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
    }
}
