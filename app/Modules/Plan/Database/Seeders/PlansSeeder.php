<?php

namespace Modules\Plan\Database\Seeders;

use App\Modules\Plan\Models\Plan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PlansSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Starter',
                'description' => 'Ideal for small admin/staff managing a few properties.',
                'max_properties' => 5,
                'price' => 0.00,
                'features' => json_encode(['basic_support', 'email_receipts']),
                'is_active' => true,
            ],
            [
                'name' => 'Pro',
                'description' => 'Perfect for growing admin/staff with more properties and advanced needs.',
                'max_properties' => 50,
                'price' => 499.00,
                'features' => json_encode(['email_support', 'pdf_reports', 'bulk_messaging']),
                'is_active' => true,
            ],
            [
                'name' => 'Enterprise',
                'description' => 'Best for large admin/staff with extensive properties and premium features.',
                'max_properties' => 999,
                'price' => 1999.00,
                'features' => json_encode(['priority_support', 'api_access', 'custom_branding']),
                'is_active' => true,
            ],
        ];

        foreach ($plans as $planData) {
            $plan = Plan::where('name', $planData['name'])->first();

            if (!$plan) {
                $planData['uuid'] = (string) Str::uuid();
            }

            Plan::updateOrCreate(
                ['name' => $planData['name']],
                $planData
            );
        }
    }
}
