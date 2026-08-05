<?php

namespace Tests\Feature;

use App\Models\Lease;
use App\Models\Plan;
use App\Models\Property;
use App\Models\PropertyUnit;
use App\Models\Renter;
use App\Models\Role;
use App\Models\TenantBusiness;
use App\Models\User;
use Database\Seeders\PermissionActionSeeder;
use Database\Seeders\PermissionModuleSeeder;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class PropertyUnitTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsTenantAdmin(string $planSlug = 'free'): User
    {
        $this->seed([
            PlanSeeder::class,
            RoleSeeder::class,
            PermissionModuleSeeder::class,
            PermissionActionSeeder::class,
            RolePermissionSeeder::class,
        ]);

        $plan = Plan::where('slug', $planSlug)->firstOrFail();

        $tenantBusiness = TenantBusiness::factory()->create(['plan_id' => $plan->id]);

        $user = User::factory()->create([
            'role_id' => Role::where('slug', 'admin')->value('id'),
            'tenant_business_id' => $tenantBusiness->id,
            'username' => 'admin_'.$tenantBusiness->id,
        ]);

        Passport::actingAs($user);

        return $user;
    }

    public function test_show_returns_active_tenants_and_occupied_count(): void
    {
        $user = $this->actingAsTenantAdmin();

        $property = Property::factory()->create(['tenant_business_id' => $user->tenant_business_id]);
        $unit = PropertyUnit::factory()->create(['property_id' => $property->id, 'capacity' => 3]);

        $activeRenters = Renter::factory()->count(2)->create(['tenant_business_id' => $user->tenant_business_id]);
        foreach ($activeRenters as $renter) {
            Lease::factory()->create([
                'property_unit_id' => $unit->id,
                'renter_id' => $renter->id,
            ]);
        }

        $terminatedRenter = Renter::factory()->create(['tenant_business_id' => $user->tenant_business_id]);
        Lease::factory()->inactive()->create([
            'property_unit_id' => $unit->id,
            'renter_id' => $terminatedRenter->id,
        ]);

        $response = $this->getJson("/api/v1/property-unit/{$unit->uuid}");

        $response->assertOk();
        $response->assertJsonPath('data.occupied_count', 2);
        $response->assertJsonCount(2, 'data.tenants');

        $tenantUuids = collect($response->json('data.tenants'))->pluck('uuid')->sort()->values()->all();
        $expectedUuids = $activeRenters->pluck('uuid')->sort()->values()->all();

        $this->assertEquals($expectedUuids, $tenantUuids);
    }

    public function test_index_returns_occupied_count_and_tenants_per_unit(): void
    {
        $user = $this->actingAsTenantAdmin();

        $property = Property::factory()->create(['tenant_business_id' => $user->tenant_business_id]);
        $unit = PropertyUnit::factory()->create(['property_id' => $property->id, 'capacity' => 2]);

        $renter = Renter::factory()->create(['tenant_business_id' => $user->tenant_business_id]);
        Lease::factory()->create([
            'property_unit_id' => $unit->id,
            'renter_id' => $renter->id,
        ]);

        $response = $this->getJson("/api/v1/property-unit?property_uuid={$property->uuid}");

        $response->assertOk();
        $unitPayload = collect($response->json('data'))->firstWhere('uuid', $unit->uuid);

        $this->assertNotNull($unitPayload);
        $this->assertSame(1, $unitPayload['occupied_count']);
        $this->assertCount(1, $unitPayload['tenants']);
        $this->assertSame($renter->uuid, $unitPayload['tenants'][0]['uuid']);
    }

    public function test_store_rejects_units_beyond_plan_max_units(): void
    {
        $user = $this->actingAsTenantAdmin(); // free plan, max_units = 5
        $plan = $user->tenantBusiness->plan;

        $property = Property::factory()->create(['tenant_business_id' => $user->tenant_business_id]);
        PropertyUnit::factory()->count(5)->create(['property_id' => $property->id]);

        $response = $this->postJson('/api/v1/property-unit', [
            'property_uuid' => $property->uuid,
            'name' => 'Overflow Unit',
            'capacity' => 1,
            'rent_price' => 5000,
            'amenity_uuids' => [],
        ]);

        $response->assertStatus(403);
        $response->assertJsonFragment([
            'message' => "Plan limit reached. Your current plan allows a maximum of {$plan->max_units} units.",
        ]);

        $this->assertSame(5, PropertyUnit::count());
    }

    public function test_store_succeeds_within_plan_max_units(): void
    {
        $user = $this->actingAsTenantAdmin(); // free plan, max_units = 5

        $property = Property::factory()->create(['tenant_business_id' => $user->tenant_business_id]);
        PropertyUnit::factory()->count(4)->create(['property_id' => $property->id]);

        $response = $this->postJson('/api/v1/property-unit', [
            'property_uuid' => $property->uuid,
            'name' => 'Final Unit',
            'capacity' => 1,
            'rent_price' => 5000,
            'amenity_uuids' => [],
        ]);

        $response->assertStatus(201);
        $this->assertSame(5, PropertyUnit::count());
    }
}
