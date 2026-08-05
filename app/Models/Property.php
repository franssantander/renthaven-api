<?php

namespace App\Models;

use App\HasHasPublicUuidTrait;
use App\Traits\BelongsToTenantBusiness;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['tenant_business_id', 'name', 'address', 'type'])]
#[Table('properties')]
class Property extends Model
{
    use BelongsToTenantBusiness, HasFactory, HasHasPublicUuidTrait, SoftDeletes;

    protected function casts(): array
    {
        return [
            'tenant_business_id' => 'integer',
        ];
    }

    public function tenantBusiness()
    {
        return $this->belongsTo(TenantBusiness::class);
    }

    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class, 'property_amenity');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(PropertyAttachment::class, 'attachable')->orderBy('sort_order');
    }

    /**
     * The lowest sort_order attachment, used as the property's cover/profile
     * image on the property list.
     */
    protected function profileImageUrl(): Attribute
    {
        return Attribute::get(fn () => $this->attachments->first()?->url);
    }
}
