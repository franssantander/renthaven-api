<?php

namespace App\Models;

use App\Enum\LeaseTermType;
use App\HasHasPublicUuidTrait;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['property_unit_id', 'renter_id', 'term_type', 'start_date', 'end_date', 'is_active'])]
#[Table('leases')]
class Lease extends Model
{

    use SoftDeletes, HasHasPublicUuidTrait;

    protected function casts(): array
    {
        return [
            'property_unit_id' => 'integer',
            'renter_id'        => 'integer',
            'term_type'        => LeaseTermType::class,
            'start_date'       => 'date',
            'end_date'         => 'date',
            'is_active'        => 'boolean',
        ];
    }

    public function renter(): BelongsTo
    {
        return $this->belongsTo(Renter::class, 'renter_id');
    }


    public function propertyUnit(): BelongsTo
    {
        return $this->belongsTo(PropertyUnit::class, 'property_unit_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(LeaseHistory::class, 'lease_id');
    }
}