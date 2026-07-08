<?php

namespace Database\Seeders;

use App\Enum\PropertyType;
use App\Enum\PropertyUnitStatus;
use App\Enum\Role;
use App\Enum\Status;
use App\Models\Lease;
use App\Models\Property;
use App\Models\PropertyUnit;
use App\Models\Renter;
use App\Models\TenantBusiness;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class PropertyManagementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Fetch or create multi-tenant SaaS context
        $tenant = TenantBusiness::firstOrCreate(
            ['id' => 1],
            ['name' => 'Apex Property Management Ltd']
        );

        // 2. Fetch correct integer role ID for users
        $tenantRoleId = DB::table('roles')
            ->where('slug', Role::TENANT->value)
            ->orWhere('name', Role::TENANT->value)
            ->value('id') ?? 3;

        // 3. Properties setup
        $properties = [
            [
                'tenant_business_id' => $tenant->id,
                'name'               => 'Grand Horizon Apartments',
                'address'            => '742 Evergreen Terrace, Sector 7G, Springfield',
                'type'               => PropertyType::APARTMENT->value ?? 'apartment',
            ],
            [
                'tenant_business_id' => $tenant->id,
                'name'               => 'Oakridge Townhomes',
                'address'            => '1042 Maple Boulevard, Whisper Valley',
                'type'               => PropertyType::TOWNHOUSE->value ?? 'townhouse',
            ]
        ];

        foreach ($properties as $propertyIndex => $propertyData) {
            $property = Property::create($propertyData);

            // 4. Create 5 structural units per property with varying real-world states
            for ($i = 1; $i <= 5; $i++) {
                $unitNumber = ($propertyIndex + 1) * 100 + $i;

                // Explicitly map scenarios so vacancy logic matches perfectly:
                // Unit 1 & 3: Vacant properties (Single & Multi capacity)
                // Unit 2 & 5: Fully occupied single-person spaces
                // Unit 4:    Fully occupied double-person spaces (Requires 2 renters)
                $capacity = ($i === 3 || $i === 4) ? 2 : 1;
                $isOccupied = ($i === 2 || $i === 4 || $i === 5);

                $unit = PropertyUnit::create([
                    'property_id' => $property->id,
                    'name'        => "Unit {$unitNumber}",
                    'capacity'    => $capacity,
                    'rent_price'  => $capacity === 2 ? 1850.00 : 1200.00,
                    'status'      => $isOccupied
                        ? (PropertyUnitStatus::OCCUPIED->value ?? 'occupied')
                        : (PropertyUnitStatus::AVAILABLE->value ?? 'available'),
                ]);

                // 5. If the unit is occupied, fill ALL available capacity slots
                if ($isOccupied) {

                    for ($slot = 1; $slot <= $capacity; $slot++) {

                        $firstName = fake()->firstName();
                        $lastName = fake()->lastName();
                        $email = fake()->unique()->safeEmail();
                        $phone = fake()->unique()->numerify('+1 (555) ###-####');
                        $userId = null;

                        // Give some tenants an online web portal user account, leave others offline
                        $shouldHaveAccount = ($i === 2 || ($i === 4 && $slot === 1));

                        if ($shouldHaveAccount) {
                            $user = User::create([
                                'role_id'            => $tenantRoleId,
                                'tenant_business_id' => $tenant->id,
                                'full_name'          => "{$firstName} {$lastName}",
                                'username'           => fake()->unique()->userName(),
                                'email'              => $email,
                                'phone'              => $phone,
                                'password'           => Hash::make('password'),
                                'email_verified_at'  => now(),
                                'status'             => Status::ACTIVE->value ?? 'active',
                            ]);

                            $userId = $user->id;
                            $metadata = ['notes' => "Active tenant account for slot {$slot}."];
                        } else {
                            $metadata = ['notes' => "Offline record for slot {$slot}. Invitation pending."];
                        }

                        // Create the renter profile record
                        $renter = Renter::create([
                            'tenant_business_id' => $tenant->id,
                            'user_id'            => $userId,
                            'first_name'         => $firstName,
                            'last_name'          => $lastName,
                            'email'              => $email,
                            'phone'              => $phone,
                            'metadata'           => json_encode($metadata),
                        ]);

                        // Create an individual lease contract binding this renter slot to the unit
                        Lease::create([
                            'property_unit_id' => $unit->id,
                            'renter_id'        => $renter->id,
                            'start_date'       => Carbon::now()->subMonths(fake()->numberBetween(1, 4))->toDateString(),
                            'end_date'         => Carbon::now()->addMonths(fake()->numberBetween(6, 12))->toDateString(),
                            'is_active'        => true,
                        ]);
                    }
                }
            }
        }
    }
}