<?php

namespace App\Models;

use App\Enum\LeaseHistoryAction;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'lease_id',
    'previous_lease_id',
    'renter_id',
    'from_property_unit_id',
    'to_property_unit_id',
    'action',
    'effective_date',
    'performed_by',
    'notes',
])]
#[Table('lease_histories')]
class LeaseHistory extends Model
{
    protected function casts(): array
    {
        return [
            'lease_id'              => 'integer',
            'previous_lease_id'     => 'integer',
            'renter_id'             => 'integer',
            'from_property_unit_id' => 'integer',
            'to_property_unit_id'   => 'integer',
            'performed_by'          => 'integer',
            'effective_date'        => 'date',
            'action'                => LeaseHistoryAction::class,
        ];
    }

    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class, 'lease_id');
    }

    public function previousLease(): BelongsTo
    {
        return $this->belongsTo(Lease::class, 'previous_lease_id');
    }

    public function renter(): BelongsTo
    {
        return $this->belongsTo(Renter::class, 'renter_id');
    }

    public function fromPropertyUnit(): BelongsTo
    {
        return $this->belongsTo(PropertyUnit::class, 'from_property_unit_id');
    }

    public function toPropertyUnit(): BelongsTo
    {
        return $this->belongsTo(PropertyUnit::class, 'to_property_unit_id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
