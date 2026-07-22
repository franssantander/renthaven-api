<?php

namespace App\Models;

use App\Enum\LedgerStatus;
use App\HasHasPublicUuidTrait;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
    'amount_paid',
    'penalty_amount',
    'penalty_applied_at',
    'period_start',
    'period_end',
    'due_date',
    'status',
    'paid_at',
    'paid_by',
    'reminder_sent_at',
    'notes',
    'or_number',
    'submitted_at',
    'submitted_amount',
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
            'lease_id' => 'integer',
            'renter_id' => 'integer',
            'property_unit_id' => 'integer',
            'tenant_business_id' => 'integer',
            'paid_by' => 'integer',
            'amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'penalty_amount' => 'decimal:2',
            'penalty_applied_at' => 'datetime',
            'period_start' => 'date',
            'period_end' => 'date',
            'due_date' => 'date',
            'paid_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
            'submitted_at' => 'datetime',
            'submitted_amount' => 'decimal:2',
            'status' => LedgerStatus::class,
        ];
    }

    /**
     * Outstanding balance still owed on this entry: the amount due plus any
     * late penalty, less whatever has actually been paid so far.
     */
    protected function balance(): Attribute
    {
        return Attribute::make(
            get: fn () => round((float) $this->amount + (float) $this->penalty_amount - (float) $this->amount_paid, 2),
        );
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
