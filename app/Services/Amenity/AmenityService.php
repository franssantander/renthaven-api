<?php

namespace App\Services\Amenity;

use App\Models\Amenity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AmenityService
{
    /**
     * Create one or more amenities, all owned by the given tenant business
     * (or the global catalog when null).
     *
     * @param  array<int, array<string, mixed>>  $amenities
     * @return Amenity[]
     */
    public function createMany(array $amenities, ?int $tenantBusinessId): array
    {
        return DB::transaction(function () use ($amenities, $tenantBusinessId) {
            $created = [];

            foreach ($amenities as $amenity) {
                $created[] = Amenity::create([
                    'tenant_business_id' => $tenantBusinessId,
                    'name'               => $amenity['name'],
                    'slug'               => $amenity['slug'] ?? Str::slug($amenity['name']),
                    'category'           => $amenity['category'] ?? null,
                    'icon'               => $amenity['icon'] ?? null,
                ]);
            }

            return $created;
        });
    }
}
