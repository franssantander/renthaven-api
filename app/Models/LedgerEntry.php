<?php

namespace App\Models;

use App\Enum\LedgerStatus;
use App\HasHasPublicUuidTrait;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'lease_id',
    'renter_id',
    'property_unit_id',
    'tenant_business_id',
    'amount',
    'period_start',
    'period_end',
    'due_date',
    'status',
    'paid_at',
    'paid_by',
    'reminder_sent_at',
    'notes',
    'submitted_at',
    'submission_reference',
    'submission_notes',
])]
#[Table('ledger_entries')]
class LedgerEntry extends Model
{
    use HasHasPublicUuidTrait, SoftDeletes;

    protected function casts(): array
    {
        return [
            'lease_id'           => 'integer',
            'renter_id'          => 'integer',
            'property_unit_id'   => 'integer',
            'tenant_business_id' => 'integer',
            'paid_by'            => 'integer',
            'amount'             => 'decimal:2',
            'period_start'       => 'date',
            'period_end'         => 'date',
            'due_date'           => 'date',
            'paid_at'            => 'datetime',
            'reminder_sent_at'   => 'datetime',
            'submitted_at'       => 'datetime',
            'status'             => LedgerStatus::class,
        ];
    }

    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }

    public function renter(): BelongsTo
    {
        return $this->belongsTo(Renter::class);
    }

    public function propertyUnit(): BelongsTo
    {
        return $this->belongsTo(PropertyUnit::class);
    }

    public function tenantBusiness(): BelongsTo
    {
        return $this->belongsTo(TenantBusiness::class);
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(PropertyAttachment::class, 'attachable')->orderBy('sort_order');
    }
}
