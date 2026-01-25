<?php

namespace App\Modules\MaintenanceManagement\Models;

use App\Modules\Property\Models\Property;
use App\Modules\TenantManagement\Models\Lease;
use App\Traits\Filterable;
use Database\Factories\MaintenancePropertyFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class MaintenanceProperty extends Model
{
    use HasFactory, HasUuids, SoftDeletes;
    use Filterable;

    protected $table = 'maintenance_properties';

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    protected static function newFactory()
    {
        return MaintenancePropertyFactory::new();
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }

    #[Scope]
    public function forUser($query, $user)
    {
        if ($user->portfolio_id) {
            return $query->where('portfolio_id', $user->portfolio_id);
        }
        return $query;
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where('uuid', $value)
            ->forUser(auth()->user())
            ->firstOrFail();
    }
}