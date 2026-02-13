<?php

namespace App\Modules\MaintenanceManagement\Models;

use App\Modules\Property\Models\Property;
use App\Modules\TenantManagement\Models\Lease;
use App\Traits\Filterable;
use App\Traits\HasMultiTenantScope;
use Database\Factories\MaintenancePropertyFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaintenanceProperty extends Model
{
    use HasFactory, HasMultiTenantScope, SoftDeletes;
    use Filterable;

    protected $table = 'maintenance_properties';

    protected $fillable = [
        'portfolio_id',
        'property_id',
        'lease_id',
        'issue_reported',
        'description',
        'priority',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $searchableRelations = [
        'issue_reported',
        'priority',
        'status',
        'property.name',
        'property.type',
    ];

    protected static function newFactory()
    {
        return MaintenancePropertyFactory::new();
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }
}