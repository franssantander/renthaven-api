<?php

namespace Database\Seeders;

use App\Enum\AmenityScope;
use App\Enum\DepositStatus;
use App\Enum\LeaseHistoryAction;
use App\Enum\LeaseTermType;
use App\Enum\PropertyType;
use App\Enum\PropertyUnitStatus;
use App\Enum\Role;
use App\Enum\Status;
use App\Models\Amenity;
use App\Models\Lease;
use App\Models\LeaseHistory;
use App\Models\Property;
use App\Models\PropertyAttachment;
use App\Models\PropertyUnit;
use App\Models\Renter;
use App\Models\TenantBusiness;
use App\Models\User;
use App\Services\Lease\LeaseService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PropertyManagementSeeder extends Seeder
{
    /**
     * Two properties per real tenant business (seeded by TenantBusinessSeeder),
     * keyed by tenant email so the mapping is explicit and doesn't depend on
     * auto-increment id ordering.
     */
    private const PROPERTY_TEMPLATES = [
        'admin@delacruzrentals.ph' => [
            ['name' => 'Dela Cruz Grand Residences', 'address' => '742 Evergreen Terrace, Sector 7G, Makati City', 'type' => PropertyType::APARTMENT],
            ['name' => 'Dela Cruz Garden Flats', 'address' => '15 Acacia Lane, Brgy. San Antonio, Makati City', 'type' => PropertyType::CONDO],
        ],
        'leasing@ubelt-dorms.com' => [
            ['name' => 'Espana Student Dormitory', 'address' => '888 España Blvd, Sampaloc, Manila', 'type' => PropertyType::DORM],
            ['name' => 'Sampaloc Scholars Hall', 'address' => '45 P. Noval St, Sampaloc, Manila', 'type' => PropertyType::DORM],
        ],
        'hello@metrosharecondos.ph' => [
            ['name' => 'BGC Sky Tower Condos', 'address' => 'Tower 2, Bonifacio Global City, Taguig City', 'type' => PropertyType::CONDO],
            ['name' => 'Metro Share Riverside Condos', 'address' => '32nd St, Bonifacio Global City, Taguig City', 'type' => PropertyType::CONDO],
        ],
        'billing@cebuhighlands.com' => [
            ['name' => 'Lahug Heights Townhomes', 'address' => 'Lahug Heights, Cebu City, Cebu', 'type' => PropertyType::TOWNHOUSE],
            ['name' => 'Cebu Uplands Residences', 'address' => 'Busay Hills, Cebu City, Cebu', 'type' => PropertyType::APARTMENT],
        ],
        'contact@apexproperties.ph' => [
            ['name' => 'Apex Ortigas Grand Tower', 'address' => 'Ortigas Center, Pasig City', 'type' => PropertyType::CONDO],
            ['name' => 'Apex Business District Lofts', 'address' => 'Bagumbayan, Pasig City', 'type' => PropertyType::APARTMENT],
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenantRoleId = DB::table('roles')
            ->where('slug', Role::TENANT->value)
            ->orWhere('name', Role::TENANT->value)
            ->value('id') ?? 3;

        $leaseService = app(LeaseService::class);

        $propertyAmenityIds = Amenity::whereIn('scope', [AmenityScope::PROPERTY->value, AmenityScope::BOTH->value])->pluck('id');
        $unitAmenityIds = Amenity::whereIn('scope', [AmenityScope::UNIT->value, AmenityScope::BOTH->value])->pluck('id');

        foreach (self::PROPERTY_TEMPLATES as $tenantEmail => $templates) {
            $tenant = TenantBusiness::where('email', $tenantEmail)->first();

            if (! $tenant) {
                continue;
            }

            foreach ($templates as $template) {
                $property = Property::create([
                    'tenant_business_id' => $tenant->id,
                    'name' => $template['name'],
                    'address' => $template['address'],
                    'type' => $template['type']->value,
                ]);

                if ($propertyAmenityIds->isNotEmpty()) {
                    $property->amenities()->sync(
                        $propertyAmenityIds->random(min($propertyAmenityIds->count(), random_int(4, 6)))->all()
                    );
                }

                $units = $this->seedUnitsAndOccupants($property, $tenant, $tenantRoleId, $unitAmenityIds, $leaseService);

                $this->seedAttachments($property, $units);
            }
        }
    }

    /**
     * Create the 3-unit occupancy scenario (available / partially occupied /
     * fully occupied) for a property, along with demo renters/users/leases.
     * Returns every created unit so the caller can attach images to each one.
     *
     * @return array<int, PropertyUnit>
     */
    private function seedUnitsAndOccupants(Property $property, TenantBusiness $tenant, int $tenantRoleId, Collection $unitAmenityIds, LeaseService $leaseService): array
    {
        $unitScenarios = [
            [
                'name' => 'Unit 101',
                'capacity' => 2,
                'status' => 'available', // 0/2 Filled
                'renters_count' => 0,
            ],
            [
                'name' => 'Unit 102',
                'capacity' => 2,
                'status' => 'partially_occupied', // 1/2 Filled
                'renters_count' => 1,
            ],
            [
                'name' => 'Unit 103',
                'capacity' => 2,
                'status' => 'occupied', // 2/2 Filled
                'renters_count' => 2,
            ],
        ];

        $units = [];

        foreach ($unitScenarios as $scenario) {
            // Units start empty/available; the real status is earned below by
            // whichever leases actually get created, via recalculateUnitStatus().
            $unit = PropertyUnit::create([
                'property_id' => $property->id,
                'name' => $scenario['name'],
                'capacity' => $scenario['capacity'],
                'rent_price' => 1950.00,
                'status' => PropertyUnitStatus::AVAILABLE->value,
            ]);

            if ($unitAmenityIds->isNotEmpty()) {
                $unit->amenities()->sync(
                    $unitAmenityIds->random(min($unitAmenityIds->count(), random_int(2, 4)))->all()
                );
            }

            $units[] = $unit;

            for ($slot = 1; $slot <= $scenario['renters_count']; $slot++) {

                $firstName = fake()->firstName();
                $lastName = fake()->lastName();
                $email = fake()->unique()->safeEmail();
                $phone = fake()->unique()->numerify('+1 (555) ###-####');
                $userId = null;

                if ($slot === 1) {
                    $user = User::create([
                        'role_id' => $tenantRoleId,
                        'tenant_business_id' => $tenant->id,
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'username' => fake()->unique()->userName(),
                        'email' => $email,
                        'phone' => $phone,
                        'password' => Hash::make('password'),
                        'email_verified_at' => now(),
                        'status' => Status::ACTIVE->value ?? 'active',
                    ]);

                    $userId = $user->id;
                }

                $renter = Renter::create([
                    'tenant_business_id' => $tenant->id,
                    'user_id' => $userId,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'phone' => $phone,
                    'metadata' => json_encode([
                        'unit_status_context' => $scenario['status'],
                        'tenant_slot_index' => $slot,
                    ]),
                ]);

                $startDate = Carbon::now()->subMonths(2)->toDateString();

                $lease = Lease::create([
                    'property_unit_id' => $unit->id,
                    'renter_id' => $renter->id,
                    'term_type' => LeaseTermType::MONTHLY,
                    'start_date' => $startDate,
                    'end_date' => Carbon::now()->addMonths(10)->toDateString(),
                    'is_active' => true,
                    // PH-standard "2 months deposit + 1 month advance" against this unit's rent.
                    'security_deposit' => $unit->rent_price * 2,
                    'advance_rent' => $unit->rent_price,
                    'deposit_status' => DepositStatus::HELD,
                ]);

                LeaseHistory::create([
                    'lease_id' => $lease->id,
                    'previous_lease_id' => null,
                    'renter_id' => $renter->id,
                    'from_property_unit_id' => null,
                    'to_property_unit_id' => $unit->id,
                    'action' => LeaseHistoryAction::ASSIGNED,
                    'effective_date' => $startDate,
                ]);
            }

            $leaseService->recalculateUnitStatus($unit);
        }

        return $units;
    }

    /**
     * Attach a couple of demo images to the property and to every one of its
     * units so the attachment endpoints have realistic data to return out of
     * the box. The lowest sort_order attachment on each model doubles as its
     * implicit "profile"/cover image in the property/unit lists, since
     * Property::attachments() and PropertyUnit::attachments() are both
     * ordered by sort_order.
     *
     * @param array<int, PropertyUnit> $units
     */
    private function seedAttachments(Property $property, array $units): void
    {
        $placeholder = $this->placeholderImageContents($property->name);

        $targets = [
            [$property, ['Front exterior view', 'Lobby entrance']],
        ];

        foreach ($units as $unit) {
            $targets[] = [$unit, ["{$unit->name} — Living room", "{$unit->name} — Kitchen"]];
        }

        foreach ($targets as [$model, $captions]) {
            foreach (array_values($captions) as $index => $caption) {
                $path = 'property-attachments/'.Str::uuid().'.png';
                Storage::disk('public')->put($path, $placeholder);

                PropertyAttachment::create([
                    'attachable_type' => $model->getMorphClass(),
                    'attachable_id' => $model->id,
                    'disk' => 'public',
                    'path' => $path,
                    'original_filename' => Str::slug($caption).'.png',
                    'mime_type' => 'image/png',
                    'size' => strlen($placeholder),
                    'caption' => $caption,
                    'sort_order' => $index + 1,
                ]);
            }
        }
    }

    private function placeholderImageContents(string $label): string
    {
        $image = imagecreatetruecolor(800, 600);
        $background = imagecolorallocate($image, 210, 214, 220);
        imagefilledrectangle($image, 0, 0, 800, 600, $background);

        $textColor = imagecolorallocate($image, 90, 98, 110);
        imagestring($image, 5, 330, 270, 'RentHaven', $textColor);
        imagestring($image, 3, 400 - (int) (strlen($label) * 3), 300, $label, $textColor);

        ob_start();
        imagepng($image);
        $contents = ob_get_clean();
        imagedestroy($image);

        return $contents;
    }
}
