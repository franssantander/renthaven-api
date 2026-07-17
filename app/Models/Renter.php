<?php

namespace App\Models;

use App\HasHasPublicUuidTrait;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['tenant_business_id', 'user_id', 'first_name', 'last_name', 'email', 'phone', 'emergency_contact'])]
#[Table('renters')]
class Renter extends Model
{

    use SoftDeletes, HasHasPublicUuidTrait;

    protected function casts(): array
    {
        return [
            'tenant_business_id' => 'integer',
            'user_id'            => 'integer',
            'emergency_contact'  => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }


    public function tenantBusiness(): BelongsTo
    {
        return $this->belongsTo(TenantBusiness::class, 'tenant_business_id');
    }


    public function leases(): HasMany
    {
        return $this->hasMany(Lease::class, 'renter_id');
    }


    public function activeLease(): HasOne
    {
        return $this->hasOne(Lease::class, 'renter_id')->where('is_active', true);
    }
}