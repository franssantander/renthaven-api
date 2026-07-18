<?php

namespace App\Models;

use App\HasHasPublicUuidTrait;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'tenant_business_id',
    'audit_log_id',
    'module',
    'action',
    'title',
    'message',
    'data',
    'read_at',
])]
#[Table('notifications')]
class Notification extends Model
{
    use HasHasPublicUuidTrait;

    protected function casts(): array
    {
        return [
            'user_id'            => 'integer',
            'tenant_business_id' => 'integer',
            'audit_log_id'       => 'integer',
            'data'               => 'array',
            'read_at'            => 'datetime',
        ];
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function auditLog(): BelongsTo
    {
        return $this->belongsTo(AuditLog::class);
    }

    public function tenantBusiness(): BelongsTo
    {
        return $this->belongsTo(TenantBusiness::class);
    }
}
