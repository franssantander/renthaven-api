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
        // 1. Context setup
        $tenant = TenantBusiness::firstOrCreate(
            ['id' => 1],
            ['name' => 'Apex Property Management Ltd']
        );

        $tenantRoleId = DB::table('roles')
            ->where('slug', Role::TENANT->value)
            ->orWhere('name', Role::TENANT->value)
            ->value('id') ?? 3;

        $properties = [
            [
                'tenant_business_id' => $tenant->id,
                'name'               => 'Grand Horizon Apartments',
                'address'            => '742 Evergreen Terrace, Sector 7G, Springfield',
                'type'               => PropertyType::APARTMENT->value ?? 'apartment',
            ]
        ];

        foreach ($properties as $propertyIndex => $propertyData) {
            $property = Property::create($propertyData);

            // 2. Structural Units layout matching all target scenarios
            $unitScenarios = [
                [
                    'name' => 'Unit 101',
                    'capacity' => 2,
                    'status' => 'available', // 0/2 Filled
                    'renters_count' => 0
                ],
                [
                    'name' => 'Unit 102',
                    'capacity' => 2,
                    'status' => 'partially_occupied', // 1/2 Filled 
                    'renters_count' => 1
                ],
                [
                    'name' => 'Unit 103',
                    'capacity' => 2,
                    'status' => 'occupied', // 2/2 Filled
                    'renters_count' => 2
                ]
            ];

            foreach ($unitScenarios as $scenario) {
                // Resolve enum values if they exist, otherwise fallback safely to string matches
                $resolvedStatus = match ($scenario['status']) {
                    'occupied'           => PropertyUnitStatus::OCCUPIED->value ?? 'occupied',
                    'partially_occupied' => defined('App\Enum\PropertyUnitStatus::PARTIALLY_OCCUPIED') ? PropertyUnitStatus::PARTIALLY_OCCUPIED->value : 'partially_occupied',
                    default              => PropertyUnitStatus::AVAILABLE->value ?? 'available',
                };

                $unit = PropertyUnit::create([
                    'property_id' => $property->id,
                    'name'        => $scenario['name'],
                    'capacity'    => $scenario['capacity'],
                    'rent_price'  => 1950.00,
                    'status'      => $resolvedStatus,
                ]);


                for ($slot = 1; $slot <= $scenario['renters_count']; $slot++) {

                    $firstName = fake()->firstName();
                    $lastName = fake()->lastName();
                    $email = fake()->unique()->safeEmail();
                    $phone = fake()->unique()->numerify('+1 (555) ###-####');
                    $userId = null;

                    if ($slot === 1) {
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
                    }

                    $renter = Renter::create([
                        'tenant_business_id' => $tenant->id,
                        'user_id'            => $userId,
                        'first_name'         => $firstName,
                        'last_name'          => $lastName,
                        'email'              => $email,
                        'phone'              => $phone,
                        'metadata'           => json_encode([
                            'unit_status_context' => $scenario['status'],
                            'tenant_slot_index'   => $slot
                        ]),
                    ]);

                    Lease::create([
                        'property_unit_id' => $unit->id,
                        'renter_id'        => $renter->id,
                        'start_date'       => Carbon::now()->subMonths(2)->toDateString(),
                        'end_date'         => Carbon::now()->addMonths(10)->toDateString(),
                        'is_active'        => true,
                    ]);
                }
            }
        }
    }
}