<?php

namespace Database\Factories;

use App\Models\Renter;
use App\Models\TenantBusiness;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Renter>
 */
class RenterFactory extends Factory
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
            'user_id' => null,
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->unique()->numerify('09#########'),
        ];
    }
}
