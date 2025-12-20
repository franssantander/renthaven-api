<?php

namespace Database\Factories;

use App\Modules\PropertyManagement\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class PropertyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */

    protected $model = Property::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->streetName . ' Apartments',
            'type' => $this->faker->randomElement(['Apartment', 'House', 'Studio']),
            'description' => $this->faker->paragraph,
            'address_line_1' => $this->faker->streetAddress,
            'city' => $this->faker->city,
            'state' => $this->faker->stateAbbr,
            'zip_code' => $this->faker->postcode,
            'number_of_rooms' => $this->faker->numberBetween(1, 5),
            'number_of_bathrooms' => $this->faker->randomFloat(1, 1, 3),
            'area_sq_ft' => $this->faker->numberBetween(500, 3000),
            'monthly_rent_price' => $this->faker->randomFloat(2, 800, 5000),
            'security_deposit' => $this->faker->randomFloat(2, 800, 5000),
            'is_available' => true,
            'is_active' => true,
            'has_parking' => $this->faker->boolean,
            'allows_pets' => $this->faker->boolean,
            'contact_email' => $this->faker->safeEmail,
            'contact_phone' => $this->faker->phoneNumber,
        ];
    }
}
