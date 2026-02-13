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
            'admin'      => Role::create(['name' => 'admin']),
            'renter'     => Role::create(['name' => 'renter']),
        ];

        // 2. Create Portfolios & Amenities
        $portfolios = Portfolio::factory()->count(5)->create();
        $this->call(AmenitySeeder::class);
        $allAmenities = Amenity::all();

        // 3. Create Super Admin
        User::factory()->create([
            'first_name' => 'Super',
            'last_name'  => 'Admin',
            'username'   => 'superadmin',
            'email'      => 'dev@renthaven.com',
            'role_id'    => $roles['superadmin']->id,
        ]);

        // 4a. Create FIXED Admin (Test Landlord)
        // We assign them the first portfolio so we know where to put the test tenant later.
        $testPortfolio = $portfolios->first();
        $testLandlord = User::factory()->create([
            'first_name'   => 'Test',
            'last_name'    => 'Landlord',
            'username'     => 'admin_user',
            'email'        => 'admin@renthaven.com',
            'role_id'      => $roles['admin']->id,
            'portfolio_id' => $testPortfolio->id, 
        ]);

        // 4b. Create Random Admins (Landlords)
        $adminUsers = User::factory()->count(29)->create([
            'role_id' => $roles['admin']->id,
        ])->each(function ($user) use ($portfolios) {
            $user->update(['portfolio_id' => $portfolios->random()->id]);
        });
        
        // Add our test landlord to the collection for property generation
        $adminUsers->push($testLandlord);

        // 5. Create Properties
        $properties = Property::factory()->count(100)->make()
            ->each(function ($property) use ($portfolios, $adminUsers, $allAmenities) {
                // Ensure some properties explicitly belong to our Test Portfolio
                static $counter = 0;
                $portfolio = ($counter < 10) ? $portfolios->first() : $portfolios->random();
                $counter++;

                $creator = $adminUsers->where('portfolio_id', $portfolio->id)->first() ?? $adminUsers->random();

                $property->portfolio_id = $portfolio->id;
                $property->created_by   = $creator->id;
                $property->updated_by   = $creator->id;
                $property->pax          = rand(1, 4);
                $property->is_available = true;
                
                $property->save();
                $property->amenities()->attach($allAmenities->random(rand(3, 6))->pluck('id')->toArray());
            });

        // 6a. Create FIXED Renter (Test Tenant)
        // Assign this user to a property owned by the Test Landlord ($testPortfolio)
        $testTenant = User::factory()->create([
            'first_name' => 'Test',
            'last_name'  => 'Tenant',
            'username'   => 'renter_user',
            'email'      => 'renter@renthaven.com',
            'role_id'    => $roles['renter']->id,
            'portfolio_id' => $testPortfolio->id,
        ]);

        $testProperty = Property::where('portfolio_id', $testPortfolio->id)->first();
        
        Lease::factory()->create([
            'user_id'      => $testTenant->id,
            'property_id'  => $testProperty->id,
            'portfolio_id' => $testPortfolio->id,
            'is_active'    => true,
            'start_date'   => Carbon::now()->subMonths(2),
            'end_date'     => Carbon::now()->addMonths(10),
        ]);

        // 6b. Create Random Renters & Assign Leases
        User::factory()->count(520)->create([
            'role_id' => $roles['renter']->id,
        ])->each(function ($user) use ($properties) {
            // (Your existing logic here remains unchanged)
            $property = $properties->random();
            $currentOccupancy = Lease::where('property_id', $property->id)->where('is_active', true)->count();

            if ($property->is_available && $currentOccupancy < $property->pax) {
                $isActive = true;
                $startDate = Carbon::now()->subMonths(rand(1, 11));
                $endDate = Carbon::now()->addMonths(rand(1, 12));
                if (($currentOccupancy + 1) >= $property->pax) {
                    $property->update(['is_available' => false]);
                }
            } else {
                $isActive = false;
                $startDate = Carbon::now()->subYears(rand(2, 5));
                $endDate = Carbon::now()->subMonths(rand(1, 12));
            }

            Lease::factory()->create([
                'user_id'      => $user->id,
                'property_id'  => $property->id,
                'portfolio_id' => $property->portfolio_id,
                'is_active'    => $isActive,
                'start_date'   => $startDate,
                'end_date'     => $endDate,
            ]);
        });

        // 7. Maintenance Logs (Existing Code)
        $createdLeases = Lease::with('property')->get();
        if ($createdLeases->count() > 0) {
            foreach ($createdLeases->random(min(50, $createdLeases->count())) as $lease) {
                MaintenanceProperty::factory()->count(rand(1, 2))->create([
                    'lease_id'     => $lease->id,
                    'property_id'  => $lease->property_id,
                    'portfolio_id' => $lease->property->portfolio_id,
                    'created_at'   => Carbon::parse($lease->start_date)->addDays(rand(5, 30)),
                ]);
            }
        }

        $this->command->info('Generating Bills for leases...');
        $this->call(BillSeeder::class);

        // 8. Passport Keys
        Artisan::call('passport:keys', ['--force' => true]);
        Artisan::call('passport:client --personal --name="Renthaven Personal Access Client" --no-interaction');

        $this->command->info('Seeding Complete!');
        $this->command->info('Test Admin: admin@renthaven.com / password');
        $this->command->info('Test Renter: renter@renthaven.com / password');
    }
}