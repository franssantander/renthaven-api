<?php

namespace App\Models;

use App\Enum\Status;
use App\HasHasPublicUuidTrait;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Table('tenant_businesses')]
#[Fillable([
    'plan_id',
    'name',
    'email',
    'phone',
    'contact_person',
    'tin',
    'business_address',
    'logo',
    'status',
    'grace_period_days',
    'late_fee_percentage',
    'or_prefix',
    'next_or_number',
])]
class TenantBusiness extends Model
{
    use HasHasPublicUuidTrait, SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => Status::class,
            'grace_period_days' => 'integer',
            'late_fee_percentage' => 'decimal:2',
            'next_or_number' => 'integer',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }
}
