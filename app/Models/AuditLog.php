<?php

namespace App\Models;

use App\HasHasPublicUuidTrait;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'user_id',
    'tenant_business_id',
    'actor_email',
    'module',
    'action',
    'description',
    'old_values',
    'new_values',
    'auditable_type',
    'auditable_id',
    'context',
    'ip_address',
    'user_agent',
])]
#[Table('audit_logs')]
class AuditLog extends Model
{
    use HasHasPublicUuidTrait;

    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'user_id'            => 'integer',
            'tenant_business_id' => 'integer',
            'auditable_id'       => 'integer',
            'context'            => 'array',
            'old_values'         => 'array',
            'new_values'         => 'array',
            'created_at'         => 'immutable_datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tenantBusiness(): BelongsTo
    {
        return $this->belongsTo(TenantBusiness::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}