<?php

namespace App\Models;

use App\Enum\DepositStatus;
use App\Enum\LeaseTermType;
use App\HasHasPublicUuidTrait;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'property_unit_id',
    'renter_id',
    'term_type',
    'start_date',
    'end_date',
    'is_active',
    'security_deposit',
    'advance_rent',
    'advance_rent_applied_at',
    'deposit_status',
    'deposit_deductions',
    'deposit_refunded_amount',
    'deposit_refunded_at',
])]
#[Table('leases')]
class Lease extends Model
{
    use HasHasPublicUuidTrait, SoftDeletes;

    protected function casts(): array
    {
        return [
            'property_unit_id' => 'integer',
            'renter_id' => 'integer',
            'term_type' => LeaseTermType::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
            'security_deposit' => 'decimal:2',
            'advance_rent' => 'decimal:2',
            'advance_rent_applied_at' => 'datetime',
            'deposit_status' => DepositStatus::class,
            'deposit_deductions' => 'array',
            'deposit_refunded_amount' => 'decimal:2',
            'deposit_refunded_at' => 'datetime',
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
