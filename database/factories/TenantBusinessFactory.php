<?php

namespace Database\Factories;

use App\Models\Plan;
use App\Models\TenantBusiness;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TenantBusiness>
 */
class TenantBusinessFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'plan_id' => Plan::inRandomOrder()->first()?->id ?? 1,
            'name' => $this->faker->company() . ' Property Rentals',
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => '09' . $this->faker->numerify('#########'),
            'contact_person' => $this->faker->name(),
            'tin' => $this->faker->numerify('###-###-###-000'),
            'business_address' => $this->faker->address(),
            'status' => $this->faker->randomElement(['active', 'inactive']),
        ];
    }
}
