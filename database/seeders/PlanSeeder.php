<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'description' => 'Try out the system. Perfect for micro-landlords testing the platform.',
                'price' => 0.00,
                'max_properties' => 2,
                'max_units' => 10,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Basic',
                'slug' => 'basic',
                'description' => 'Perfect for landlords managing a single property or small building.',
                'price' => 499.00,
                'max_properties' => 5,
                'max_units' => 10,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'description' => 'For growing rental businesses managing multiple properties or dorms.',
                'price' => 1499.00,
                'max_properties' => 20,
                'max_units' => 50,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Unlimited properties and units for large property management operations.',
                'price' => 3999.00,
                'max_properties' => 9999,
                'max_units' => 9999,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];

        foreach ($plans as $plan) {
            DB::table('plans')->updateOrInsert(
                ['slug' => $plan['slug']],
                $plan
            );
        }
    }
}
