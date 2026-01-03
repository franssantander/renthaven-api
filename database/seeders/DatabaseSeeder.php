<?php

namespace Database\Seeders;


use App\Modules\Authentication\Models\Role;
use App\Modules\Authentication\Models\User;
use App\Modules\Portfolio\Models\Portfolio;
use App\Modules\PropertyManagement\Models\Property;
use App\Modules\TenantManagement\Models\Lease;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // 1. Create Roles
        $roles = [
            'superadmin' => Role::create(['name' => 'superadmin']),
            'admin' => Role::create(['name' => 'admin']),
            'renter' => Role::create(['name' => 'renter']),
        ];

        // 2. Create Portfolios
        $portfolios = Portfolio::factory()->count(5)->create();

        // 3. Create Super Admin
        User::factory()->create([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'username' => 'superadmin',
            'email' => 'dev@renthaven.com',
            'role_id' => $roles['superadmin']->id,
        ]);

        // 4. Create Admins (Landlords) - Assign to Portfolios
        $adminUsers = User::factory()->count(29)->create([
            'role_id' => $roles['admin']->id,
        ])->each(function ($user) use ($portfolios) {
            $user->update(['portfolio_id' => $portfolios->random()->id]);
        });

        // 5. Create Properties - Assign to Portfolios and Creators
        $properties = Property::factory()->count(100)->make()
            ->each(function ($property) use ($portfolios, $adminUsers) {
                $portfolio = $portfolios->random();

                // Find an admin belonging to this portfolio, or fallback to random
                $creator = $adminUsers->where('portfolio_id', $portfolio->id)->random()
                    ?? $adminUsers->random();

                $property->portfolio_id = $portfolio->id;
                $property->created_by = $creator->id;
                $property->updated_by = $creator->id;
                $property->save();
            });

        // 6. Create Renters - AND Create Leases
        User::factory()->count(220)->create([
            'role_id' => $roles['renter']->id,
        ])->each(function ($user) use ($properties) {

            // Pick a random property for this renter
            $property = $properties->random();

            // Create the Lease record instead of updating the user table directly
            Lease::factory()->create([
                'user_id' => $user->id,
                'property_id' => $property->id,
                // Optional: If you want to track which portfolio the lease belongs to easily
                // 'portfolio_id' => $property->portfolio_id, 
            ]);

            // Note: We do NOT set property_id on the user anymore.
            // If you still have portfolio_id on users table and want to track it for renters:
            $user->update(['portfolio_id' => $property->portfolio_id]);
        });

        $this->command->info('Seeding Complete!');
        $this->command->info('Total Users: ' . User::count() . ' (Target: 250)');
        $this->command->info('Total Properties: ' . Property::count() . ' (Target: 100)');
        $this->command->info('Total Leases: ' . Lease::count() . ' (Target: 220)');
    }
}
