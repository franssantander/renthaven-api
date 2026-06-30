<?php

namespace App\Modules\RenterManagement\Models;

use App\Modules\Property\Models\Property;
use App\Traits\HasMultiTenantScope;
use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\RenterManagement\Models\RenterUser;
use Modules\RenterManagement\Database\Factories\LeaseFactory;

class Lease extends Model
{
    use HasFactory, HasMultiTenantScope, SoftDeletes;
    use Filterable;

    protected $guarded = [];

    const TYPE_FIXED = 'fixed';
    const TYPE_MONTHLY = 'monthly';

    protected $table = 'leases';

    protected $fillable = [
        'renter_user_id',
        'property_id',
        'tenant_id',
        'monthly_rent',
        'unit_number',
        'lease_type',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    protected $searchableRelations = [
        'renters.first_name',
        'renters.last_name',
        'renters.email',
        'renters.username',
        'property.name',
    ];


    protected static function newFactory()
    {
        return LeaseFactory::new();
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function renter(): BelongsTo
    {
        return $this->belongsTo(RenterUser::class, 'renter_user_id');
    }
}
