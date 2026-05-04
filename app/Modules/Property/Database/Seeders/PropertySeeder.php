<?php

namespace Modules\Property\Database\Seeders;

use App\Modules\Property\Models\Amenity;
use App\Modules\Property\Models\Property;
use App\Modules\Tenant\Models\Tenant;
use Illuminate\Database\Seeder;

class PropertySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenants = Tenant::all();
        $amenities = Amenity::all();

        if ($tenants->isEmpty()) {
            $this->command->warn('No tenants found. Seeding properties skipped.');
            return;
        }

        foreach ($tenants as $tenant) {
            Property::factory()
                ->count(100)
                ->create([
                    'tenant_id' => $tenant->id,
                ])
                ->each(function ($property) use ($amenities) {
                    $property->amenities()->attach(
                        $amenities->random(rand(1, 5))->pluck('id')->toArray()
                    );
                });
        }

        $this->command->info('Properties seeded successfully for all tenants!');
    }

}
