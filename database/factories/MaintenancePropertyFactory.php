<?php

namespace Database\Factories;

use App\Modules\MaintenanceManagement\Models\MaintenanceProperty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class MaintenancePropertyFactory extends Factory
{

    protected $model = MaintenanceProperty::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'issue_reported' => $this->faker->randomElement([
                'Leaking Faucet',
                'Broken AC',
                'Electrical Spark',
                'Clogged Drain',
                'Roof Leak',
                'Pest Control',
                'Broken Window'
            ]),
            'description' => $this->faker->sentence(), 
            'priority' => $this->faker->randomElement(['low', 'medium', 'critical']),
            'status' => $this->faker->randomElement(['pending', 'in_progress', 'completed', 'cancelled']),
        ];
    }
}
