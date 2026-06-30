<?php

namespace Modules\RenterManagement\Database\Factories;

use App\Modules\RenterManagement\Models\Lease;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class LeaseFactory extends Factory
{
    protected $model = Lease::class;

    public function definition(): array
    {
        // Start date sometime in the last 6 months
        $startDate = $this->faker->dateTimeBetween('-6 months', 'now');

        return [
            'uuid' => (string) Str::uuid(),
            'start_date' => $startDate->format('Y-m-d'),
            // End date is 1 year after start date
            'end_date' => $this->faker->dateTimeBetween('now', '+1 year')->format('Y-m-d'),
            'lease_type' => 'monthly',
            'is_active' => true,
        ];
    }

    public function fixed(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'lease_type' => 'fixed',

                'end_date' => \Carbon\Carbon::parse($attributes['start_date'] ?? now())
                    ->addYear()
                    ->toDateString(),
            ];
        });
    }
}