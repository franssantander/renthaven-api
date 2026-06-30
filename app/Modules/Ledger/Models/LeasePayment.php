<?php

namespace App\Modules\Ledger\Models;

use App\Enums\LeasePaymentEnum;
use App\Modules\RenterManagement\Models\Lease;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Ledger\Database\Factories\LeasePaymentFactory;

class LeasePayment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'lease_payments';
    protected $fillable = [
        'uuid',
        'lease_id',
        'billing_period',
        'due_date',
        'amount_due',
        'amount_paid',
        'paid_at',
        'reference_no',
        'payment_method',
        'proof_path',
        'status',
    ];

    protected $casts = [
        'due_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class, 'lease_id');
    }

    protected static function newFactory()
    {
        return LeasePaymentFactory::new();
    }

    public function getComputedStatusAttribute()
    {
        if ($this->status === LeasePaymentEnum::PAID->value)
            return 'Paid';
        if ($this->status === LeasePaymentEnum::REJECTED->value)
            return 'Pending';
        return now()->gt($this->due_date) ? 'Overdue' : 'Pending';
    }
}