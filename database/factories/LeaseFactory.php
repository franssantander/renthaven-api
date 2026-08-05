<?php

namespace Database\Factories;

use App\Enum\LeaseTermType;
use App\Models\Lease;
use App\Models\PropertyUnit;
use App\Models\Renter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lease>
 */
class LeaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'property_unit_id' => PropertyUnit::factory(),
            'renter_id' => Renter::factory(),
            'term_type' => LeaseTermType::MONTHLY,
            'start_date' => now()->subMonth(),
            'end_date' => null,
            'is_active' => true,
            'security_deposit' => 0,
            'advance_rent' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'end_date' => now()->subDay(),
        ]);
    }
}
