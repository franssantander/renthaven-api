<?php

namespace Database\Factories;

use App\Enum\PropertyUnitStatus;
use App\Models\Property;
use App\Models\PropertyUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PropertyUnit>
 */
class PropertyUnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'name' => 'Unit '.$this->faker->unique()->bothify('##?'),
            'capacity' => 3,
            'rent_price' => $this->faker->numberBetween(3000, 20000),
            'status' => PropertyUnitStatus::AVAILABLE,
        ];
    }
}
