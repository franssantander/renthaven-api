<?php

namespace Modules\RenterManagement\Database\Factories;

use App\Modules\RenterManagement\Models\RenterUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class RenterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = RenterUser::class;

    public function definition(): array
    {
        return [
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'email' => $this->faker->unique()->safeEmail,
            'phone_number' => $this->faker->phoneNumber,
            'username' => $this->faker->unique()->userName(),
            'password' => Hash::make('password'),
            'is_active' => true,
            'tenant_id' => null,
        ];
    }
}
