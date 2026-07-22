<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TenantBusinessSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $freePlanId = DB::table('plans')->where('slug', 'free')->value('id') ?? 1;
        $basicPlanId = DB::table('plans')->where('slug', 'basic')->value('id') ?? 2;
        $proPlanId = DB::table('plans')->where('slug', 'pro')->value('id') ?? 3;
        $enterprisePlanId = DB::table('plans')->where('slug', 'enterprise')->value('id') ?? 4;

        $businesses = [
            [
                'plan_id' => $freePlanId,
                'name' => 'Dela Cruz Apartments',
                'email' => 'admin@delacruzrentals.ph',
                'phone' => '09171234567',
                'contact_person' => 'Juan Dela Cruz',
                'tin' => '123-456-789-000',
                'business_address' => '123 Multi-Family St., Brgy. San Antonio, Makati City',
                'status' => 'active',
                'grace_period_days' => 5,
                'late_fee_percentage' => 2.00,
                'or_prefix' => 'DCA',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'plan_id' => $basicPlanId,
                'name' => 'Sampaloc University Belt Dorms',
                'email' => 'leasing@ubelt-dorms.com',
                'phone' => '09189876543',
                'contact_person' => 'Maria Santos',
                'tin' => '987-654-321-000',
                'business_address' => '888 España Blvd, Sampaloc, Manila',
                'status' => 'active',
                'grace_period_days' => 3,
                'late_fee_percentage' => 5.00,
                'or_prefix' => 'UBD',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'plan_id' => $proPlanId,
                'name' => 'Metro Share Condominiums',
                'email' => 'hello@metrosharecondos.ph',
                'phone' => '09225554433',
                'contact_person' => 'Engr. Renato Luna',
                'tin' => '456-123-789-001',
                'business_address' => 'Tower 2, Bonifacio Global City, Taguig City',
                'status' => 'active',
                'grace_period_days' => 5,
                'late_fee_percentage' => 0,
                'or_prefix' => 'MSC',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'plan_id' => $proPlanId,
                'name' => 'Cebu Highlands Housing Corp',
                'email' => 'billing@cebuhighlands.com',
                'phone' => '09334441122',
                'contact_person' => 'Christina Garcia',
                'tin' => '321-789-456-000',
                'business_address' => 'Lahug Heights, Cebu City, Cebu',
                'status' => 'active',
                'grace_period_days' => 7,
                'late_fee_percentage' => 3.00,
                'or_prefix' => 'CHH',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'plan_id' => $enterprisePlanId,
                'name' => 'Apex Property Management Group',
                'email' => 'contact@apexproperties.ph',
                'phone' => '0281234567',
                'contact_person' => 'Director Alejandro Valdez',
                'tin' => '555-666-777-000',
                'business_address' => 'Penthouse, Apex Tower, Ortigas Center, Pasig City',
                'status' => 'active',
                'grace_period_days' => 5,
                'late_fee_percentage' => 2.50,
                'or_prefix' => 'APX',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];

        foreach ($businesses as $business) {
            DB::table('tenant_businesses')->updateOrInsert(
                ['email' => $business['email']],
                $business
            );
        }
    }
}
