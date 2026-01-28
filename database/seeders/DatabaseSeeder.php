<?php

namespace Database\Seeders;

use App\Modules\Authentication\Models\Role;
use App\Modules\Authentication\Models\User;
use App\Modules\MaintenanceManagement\Models\MaintenanceProperty;
use App\Modules\Portfolio\Models\Portfolio;
use App\Modules\Property\Models\Amenity;
use App\Modules\Property\Models\Property;
use App\Modules\TenantManagement\Models\Lease;
use Carbon\Carbon;
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

        // 4. Create Admins (Landlords)
        $adminUsers = User::factory()->count(29)->create([
            'role_id' => $roles['admin']->id,
        ])->each(function ($user) use ($portfolios) {
            $user->update(['portfolio_id' => $portfolios->random()->id]);
        });

        // 5. Create Properties (Initialize ALL as Available first)
        $properties = Property::factory()->count(100)->make()
            ->each(function ($property) use ($portfolios, $adminUsers, $allAmenities) {
                $portfolio = $portfolios->random();

                $creator = $adminUsers->where('portfolio_id', $portfolio->id)->random()
                    ?? $adminUsers->random();

                $property->portfolio_id = $portfolio->id;
                $property->created_by = $creator->id;
                $property->updated_by = $creator->id;

                // FORCE TRUE: We start with an empty building
                $property->is_available = true;

                $property->save();
                $property->amenities()->attach($allAmenities->random(rand(3, 6))->pluck('id')->toArray());
            });

        // 6. Create Renters & Match Leases intelligently
        User::factory()->count(520)->create([
            'role_id' => $roles['renter']->id,
        ])->each(function ($user) use ($properties) {

            // Pick a random property
            $property = $properties->random();

            // Refresh property to check its *current* availability status in DB
            $property->refresh();

            // LOGIC: If property is available, this user becomes the CURRENT tenant.
            // If it's already taken, this user becomes a PAST tenant (History).
            if ($property->is_available) {
                $isActive = true;
                $startDate = Carbon::now()->subMonths(rand(1, 11)); // Started recently
                $endDate = Carbon::now()->addMonths(rand(1, 12)); // Ends in future

                // IMPORTANT: Mark property as occupied now
                $property->update(['is_available' => false]);
            } else {
                $isActive = false;
                $startDate = Carbon::now()->subYears(rand(2, 5));
                $endDate = Carbon::now()->subMonths(rand(1, 12)); // Ended in past
            }

            Lease::factory()->create([
                'user_id' => $user->id,
                'property_id' => $property->id,
                'portfolio_id' => $property->portfolio_id,
                'is_active' => $isActive,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);

            // (Optional) Update user portfolio to match
            // $user->update(['portfolio_id' => $property->portfolio_id]);
        });

        // 7. Maintenance Logs
        $createdLeases = Lease::with('property')->get();

        foreach ($createdLeases->random(50) as $lease) {
            MaintenanceProperty::factory()->count(rand(1, 2))->create([
                'lease_id' => $lease->id,
                'property_id' => $lease->property_id,
                'portfolio_id' => $lease->property->portfolio_id,
                // Match the maintenance date to be within the lease period
                'created_at' => Carbon::parse($lease->start_date)->addDays(rand(5, 30)),
            ]);
        }

        Artisan::call('passport:keys', ['--force' => true]);
        Artisan::call('passport:client --personal --name="Renthaven Personal Access Client" --no-interaction');

        $this->command->info('Seeding Complete!');
        $this->command->info('Total Users: ' . User::count());
        $this->command->info('Total Properties: ' . Property::count());
        $this->command->info('Occupied Properties: ' . Property::where('is_available', false)->count());
        $this->command->info('Total Leases: ' . Lease::count());
    }
}