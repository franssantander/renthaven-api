<?php

namespace App\Models;

use App\Enum\PropertyUnitStatus;
use App\Enum\Role;
use App\HasHasPublicUuidTrait;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

#[Fillable(['property_id', 'name', 'capacity', 'rent_price', 'status'])]
#[Table('property_units')]
class PropertyUnit extends Model
{
    use HasFactory, HasHasPublicUuidTrait, SoftDeletes;

    protected $appends = ['occupied_count', 'tenants'];

    /**
     * property_units has no tenant_business_id column of its own — tenancy is
     * derived through its property, so this can't reuse BelongsToTenantBusiness
     * directly. Mirrors the same global-scope semantics via that relation.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('tenant_business', function (Builder $builder) {
            $user = Auth::user();

            if (! $user) {
                return; // e.g. console/queue context — no scoping applied
            }

            if ($user->role?->slug === Role::SUPER_ADMIN->value) {
                return; // super admin sees everything, unscoped
            }

            $builder->whereHas('property', function (Builder $propertyQuery) use ($user) {
                $propertyQuery->where('tenant_business_id', $user->tenant_business_id);
            });
        });
    }

    protected function casts(): array
    {
        return [
            'property_id' => 'integer',
            'status' => PropertyUnitStatus::class,
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class, 'property_unit_amenity');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(PropertyAttachment::class, 'attachable')->orderBy('sort_order');
    }

    public function leases(): HasMany
    {
        return $this->hasMany(Lease::class);
    }

    public function activeLeases(): HasMany
    {
        return $this->hasMany(Lease::class)->where('is_active', true);
    }

    protected function occupiedCount(): Attribute
    {
        return Attribute::get(fn () => $this->activeLeases->count());
    }

    protected function tenants(): Attribute
    {
        return Attribute::get(fn () => $this->activeLeases->pluck('renter')->filter()->values()->all());
    }
}
