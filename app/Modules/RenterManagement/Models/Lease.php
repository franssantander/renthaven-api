<?php

namespace App\Modules\RenterManagement\Models;

use App\Modules\Authentication\Models\User;
use App\Modules\Property\Models\Property;
use App\Traits\Filterable;
use App\Traits\HasMultiTenantScope;
use Database\Factories\LeaseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lease extends Model
{
    use HasFactory, HasMultiTenantScope, SoftDeletes;
    use Filterable;

    protected $guarded = [];

    protected $table = 'leases';

    protected $fillable = [
        'user_id',
        'property_id',
        'portfolio_id',
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
        'user.first_name',
        'user.last_name',
        'user.email',
        'user.username',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
