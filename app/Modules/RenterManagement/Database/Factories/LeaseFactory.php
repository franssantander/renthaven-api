<?php

namespace Modules\RenterManagement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-1 year', 'now');
        return [
            'uuid' => (string) Str::uuid(),
            'start_date' => $startDate,
            'end_date' => $this->faker->dateTimeBetween($startDate, '+1 year'),
            'is_active' => true,
        ];
    }
}
