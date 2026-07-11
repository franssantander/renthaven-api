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
#[Fillable(['plan_id', 'name', 'email', 'phone', 'contact_person', 'tin', 'business_address', 'logo', 'status'])]
class TenantBusiness extends Model
{
    use HasHasPublicUuidTrait, SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => Status::class
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