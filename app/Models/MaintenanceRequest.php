<?php

namespace App\Models;

use App\Enum\MaintenanceCategory;
use App\Enum\MaintenancePriority;
use App\Enum\MaintenanceRequestStatus;
use App\HasHasPublicUuidTrait;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'property_unit_id',
    'lease_id',
    'renter_id',
    'tenant_business_id',
    'title',
    'description',
    'category',
    'priority',
    'status',
    'assigned_to',
    'resolved_at',
    'resolution_notes',
])]
#[Table('maintenance_requests')]
class MaintenanceRequest extends Model
{
    use HasHasPublicUuidTrait, SoftDeletes;

    protected function casts(): array
    {
        return [
            'property_unit_id'   => 'integer',
            'lease_id'           => 'integer',
            'renter_id'          => 'integer',
            'tenant_business_id' => 'integer',
            'assigned_to'        => 'integer',
            'category'           => MaintenanceCategory::class,
            'priority'           => MaintenancePriority::class,
            'status'             => MaintenanceRequestStatus::class,
            'resolved_at'        => 'datetime',
        ];
    }

    public function propertyUnit(): BelongsTo
    {
        return $this->belongsTo(PropertyUnit::class);
    }

    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }

    public function renter(): BelongsTo
    {
        return $this->belongsTo(Renter::class);
    }

    public function tenantBusiness(): BelongsTo
    {
        return $this->belongsTo(TenantBusiness::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(MaintenanceRequestHistory::class);
    }
}
