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

        // 2. Create Portfolios & Amenities
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

        // 5. Create Properties
        $properties = Property::factory()->count(100)->make()
            ->each(function ($property) use ($portfolios, $adminUsers, $allAmenities) {
                $portfolio = $portfolios->random();
                $creator = $adminUsers->where('portfolio_id', $portfolio->id)->random() ?? $adminUsers->random();

                $property->portfolio_id = $portfolio->id;
                $property->created_by = $creator->id;
                $property->updated_by = $creator->id;

                // Initialize details
                $property->pax = rand(1, 4); // Random capacity per property
                $property->is_available = true;       // Start empty
    
                $property->save();
                $property->amenities()->attach($allAmenities->random(rand(3, 6))->pluck('id')->toArray());
            });

        // 6. Create Renters & Assign Leases based on Pax Availability
        User::factory()->count(520)->create([
            'role_id' => $roles['renter']->id,
        ])->each(function ($user) use ($properties) {

            // Pick a random property
            $property = $properties->random();

            // Count how many ACTIVE leases this property currently has
            $currentOccupancy = Lease::where('property_id', $property->id)
                ->where('is_active', true)
                ->count();

            // LOGIC: Room is available ONLY IF current tenants < pax limit
            // AND the property is marked as available
            if ($property->is_available && $currentOccupancy < $property->pax) {

                // --- CREATE ACTIVE LEASE ---
                $isActive = true;
                $startDate = Carbon::now()->subMonths(rand(1, 11));
                $endDate = Carbon::now()->addMonths(rand(1, 12));

                // CHECK: Did this specific user just fill up the last spot?
                // currentOccupancy + 1 (this user) == pax limit
                if (($currentOccupancy + 1) >= $property->pax) {
                    $property->update(['is_available' => false]);
                }

            } else {

                // --- CREATE HISTORICAL LEASE (Room Full or Unavailable) ---
                $isActive = false;
                $startDate = Carbon::now()->subYears(rand(2, 5));
                $endDate = Carbon::now()->subMonths(rand(1, 12));
            }

            Lease::factory()->create([
                'user_id' => $user->id,
                'property_id' => $property->id,
                'portfolio_id' => $property->portfolio_id,
                'is_active' => $isActive,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);
        });

        // 7. Maintenance Logs
        // Only attach maintenance to leases that actually exist
        $createdLeases = Lease::with('property')->get();

        if ($createdLeases->count() > 0) {
            foreach ($createdLeases->random(min(50, $createdLeases->count())) as $lease) {
                MaintenanceProperty::factory()->count(rand(1, 2))->create([
                    'lease_id' => $lease->id,
                    'property_id' => $lease->property_id,
                    'portfolio_id' => $lease->property->portfolio_id,
                    'created_at' => Carbon::parse($lease->start_date)->addDays(rand(5, 30)),
                ]);
            }
        }

        $this->command->info('Generating Bills for leases...');
        $this->call(BillSeeder::class);

        // 8. Passport Keys (Ensure you ran the chmod fixes before this!)
        Artisan::call('passport:keys', ['--force' => true]);
        Artisan::call('passport:client --personal --name="Renthaven Personal Access Client" --no-interaction');

        $this->command->info('Seeding Complete!');
        $this->command->info('Total Properties: ' . Property::count());
        $this->command->info('Fully Occupied Properties: ' . Property::where('is_available', false)->count());
        $this->command->info('Active Leases: ' . Lease::where('is_active', true)->count());
    }
}