<?php

namespace Modules\Tenant\Database\Factories;

use App\Modules\Plan\Models\Plan;
use App\Modules\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class TenantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */

    protected $model = Tenant::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'plan_id' => Plan::inRandomOrder()->first()->id ?? 1,
            'name' => $this->faker->company,
            'url' => $this->faker->unique()->domainName,
            'email' => $this->faker->unique()->safeEmail,
            'contact_number' => $this->faker->phoneNumber,
            'logo' => $this->faker->imageUrl(200, 200, 'business'),
            'settings' => json_encode([
                'theme_color' => $this->faker->hexColor,
                'timezone' => $this->faker->timezone,
                'currency' => $this->faker->currencyCode
            ]),
            'status' => $this->faker->randomElement(['active', 'inactive', 'suspended']),
        ];
    }
}
