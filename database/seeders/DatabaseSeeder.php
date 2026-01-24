<?php

namespace Database\Seeders;


use App\Modules\Authentication\Models\Role;
use App\Modules\Authentication\Models\User;
use App\Modules\MaintenanceManagement\Models\MaintenanceProperty;
use App\Modules\Portfolio\Models\Portfolio;
use App\Modules\Property\Models\Amenity;
use App\Modules\Property\Models\Property;
use App\Modules\TenantManagement\Models\Lease;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

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
        $this->call(AmenitySeeder::class);
        $allAmenities = Amenity::all();

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
            ->each(function ($property) use ($portfolios, $adminUsers, $allAmenities) {
                $portfolio = $portfolios->random();

                // Find an admin belonging to this portfolio, or fallback to random
                $creator = $adminUsers->where('portfolio_id', $portfolio->id)->random()
                    ?? $adminUsers->random();

                $property->portfolio_id = $portfolio->id;
                $property->created_by = $creator->id;
                $property->updated_by = $creator->id;
                $property->save();

                $property->amenities()->attach($allAmenities->random(rand(3, 6))->pluck('id')->toArray());
            });

        // 6. Create Renters - AND Create Leases
        User::factory()->count(520)->create([
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

        $createdLeases = Lease::with('property')->get();

        // Now this will work because $createdLeases contains 520 items
        foreach ($createdLeases->random(50) as $lease) {
            MaintenanceProperty::factory()->count(rand(1, 2))->create([
                'lease_id' => $lease->id,
                'property_id' => $lease->property_id,
                'portfolio_id' => $lease->property->portfolio_id,
            ]);
        }

        Artisan::call('passport:keys', ['--force' => true]);

        // Artisan::call('passport:client', [
        //     '--personal' => true,
        //     'name' => 'Renthaven Personal Access Client',
        //     '--no-interaction' => true,
        // ]);

        Artisan::call('passport:client --personal --name="Renthaven Personal Access Client" --no-interaction');

        $this->command->info('Seeding Complete!');
        $this->command->info('Total Users: ' . User::count() . ' (Target: 550)');
        $this->command->info('Total Properties: ' . Property::count() . ' (Target: 100)');
        $this->command->info('Total Leases: ' . Lease::count() . ' (Target: 520)');
        $this->command->info('Passport Personal Access Client Created Successfully.');
    }
}
