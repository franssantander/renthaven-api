<?php

namespace Database\Factories;

use App\Enum\PropertyType;
use App\Models\Property;
use App\Models\TenantBusiness;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Property>
 */
class PropertyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_business_id' => TenantBusiness::factory(),
            'name' => $this->faker->company().' Building',
            'address' => $this->faker->address(),
            'type' => $this->faker->randomElement(PropertyType::cases())->value,
        ];
    }
}
