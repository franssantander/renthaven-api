<?php

namespace App\Modules\TenantManagement\Models;

use App\Modules\Authentication\Models\User;
use App\Modules\Property\Models\Property;
use App\Traits\Filterable;
use Database\Factories\LeaseFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Lease extends Model
{
    use HasFactory, HasUuids, SoftDeletes;
    use Filterable;

    protected $guarded = [];

    protected $table = 'leases';

    protected $fillable = [
        'user_id',
        'property_id',
        'portfolio_id',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    protected $searchableRelations = [
        'user.first_name',
        'user.last_name',
        'user.email',
        'user.username',
        'property.name',
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

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    protected static function newFactory()
    {
        return LeaseFactory::new();
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
