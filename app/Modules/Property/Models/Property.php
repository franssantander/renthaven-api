<?php

namespace App\Modules\Property\Models;

use App\Modules\Authentication\Models\User;
use App\Modules\Portfolio\Models\Portfolio;
use App\Modules\TenantManagement\Models\Lease;
use App\Traits\Filterable;
use App\Traits\HasMultiTenantScope;
use Database\Factories\PropertyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Lcobucci\JWT\Token\Builder;

class Property extends Model
{
    use HasFactory, HasMultiTenantScope, SoftDeletes;
    use Filterable;

    protected $table = 'properties';

    protected $fillable = [
        'name',
        'type',
        'description',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'zip_code',
        'number_of_rooms',
        'number_of_bathrooms',
        'area_sq_ft',
        'monthly_rent_price',
        'security_deposit',
        'is_available',
        'pax',
        'is_active',
        'has_parking',
        'allows_pets',
        'contact_email',
        'contact_phone',
        'portfolio_id',
        'created_by',
        'updated_by',
    ];

    protected $searchableRelations = [
        'portfolio.name',
        'createdBy.first_name',
        'createdBy.last_name',
        'createdBy.email',
        'createdBy.username',
    ];

    protected $exactFilters = [
        'type',
        'status',
        'is_active',
        'is_available',
        'has_parking',
        'allows_pets',
        'number_of_rooms',
        'portfolio_id'
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'is_active' => 'boolean',
        'has_parking' => 'boolean',
        'allows_pets' => 'boolean',
        'monthly_rent_price' => 'decimal:2',
        'security_deposit' => 'decimal:2',
        'number_of_bathrooms' => 'decimal:1',
    ];


    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class, 'amenity_property_pivot')
            ->using(AmenityPropertyPivot::class)
            ->wherePivot('deleted_at', null);
    }

    public function activeLeases(): HasMany
    {
        return $this->hasMany(Lease::class)
            ->where('is_active', true)
            ->with('user');
    }

    public function updateAvailability(): bool
    {
        $activeCount = $this->activeLeases()->count();
        $this->is_available = $activeCount < $this->pax;
        return $this->save();
    }

    protected static function booted()
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $builder->where('tenant_id', auth()->user()->current_tenant_id);
        });
    }
}
