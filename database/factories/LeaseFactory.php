<?php

namespace Database\Factories;

use App\Modules\RenterManagement\Models\Lease;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class LeaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Lease::class;

    public function definition(): array
    {
        return [
            'start_date' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'end_date' => null,
            'lease_type' => Lease::TYPE_MONTHLY,
            'is_active' => true,
        ];
    }

    public function fixed(): static
    {
        return $this->state(fn(array $attributes) => [
            'lease_type' => Lease::TYPE_FIXED,
            'end_date' => $this->faker->dateTimeBetween('now', '+1 year')->format('Y-m-d'),
        ]);
    }
}
