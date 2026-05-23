<?php

namespace Modules\Property\Database\Factories;

use App\Modules\Property\Models\Property;
use App\Modules\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class PropertyFactory extends Factory
{
    protected $model = Property::class;

    public function definition(): array
    {
        $isShared = $this->faker->boolean(30);

        $type = $isShared ? 'condo_sharing' : $this->faker->randomElement(['apartment_building', 'residence', 'house']);

        // 3. Set capacities based on the business model
        if ($isShared) {
            $totalUnits = 1; // Condo sharing is just 1 physical unit
            $pax = $this->faker->numberBetween(3, 8); // 3 to 8 beds available
        } else {
            $totalUnits = $this->faker->numberBetween(1, 12); // A building with 1 to 12 apartments
            $pax = $this->faker->numberBetween(2, 6); // Max family size per apartment
        }

        return [
            'uuid' => (string) Str::uuid(),
            'name' => $this->faker->streetName . ($isShared ? ' Condo Sharing' : ' Apartments'),
            'type' => $type,
            'description' => $this->faker->paragraph,

            'address_line_1' => $this->faker->streetAddress,
            'address_line_2' => $this->faker->secondaryAddress,
            'city' => $this->faker->city,
            'state' => $this->faker->stateAbbr,
            'zip_code' => $this->faker->postcode,

            'number_of_rooms' => $this->faker->numberBetween(1, 5),
            'number_of_bathrooms' => $this->faker->randomFloat(1, 1, 3),
            'area_sq_ft' => $this->faker->numberBetween(500, 3000),

            'monthly_rent_price' => $this->faker->randomFloat(2, 800, 5000),
            'security_deposit' => $this->faker->randomFloat(2, 800, 5000),

            'is_shared' => $isShared,
            'total_units' => $totalUnits,
            'pax' => $pax,
            'occupied' => 0,

            'is_available' => true,
            'is_active' => true,
            'has_parking' => $this->faker->boolean,
            'allows_pets' => $this->faker->boolean,

            'contact_email' => $this->faker->safeEmail,
            'contact_phone' => $this->faker->phoneNumber,
            'tenant_id' => Tenant::first()->id ?? 1,
        ];
    }
}
