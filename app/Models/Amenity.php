<?php

namespace App\Models;

use App\Enum\AmenityCategory;
use App\Enum\AmenityScope;
use App\HasHasPublicUuidTrait;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['tenant_business_id', 'name', 'slug', 'category', 'scope', 'icon'])]
#[Table('amenities')]
class Amenity extends Model
{
    use HasHasPublicUuidTrait, SoftDeletes;

    protected function casts(): array
    {
        return [
            'tenant_business_id' => 'integer',
            'category'           => AmenityCategory::class,
            'scope'              => AmenityScope::class,
        ];
    }

    public function tenantBusiness(): BelongsTo
    {
        return $this->belongsTo(TenantBusiness::class);
    }

    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class, 'property_amenity');
    }

    public function propertyUnits(): BelongsToMany
    {
        return $this->belongsToMany(PropertyUnit::class, 'property_unit_amenity');
    }
}
