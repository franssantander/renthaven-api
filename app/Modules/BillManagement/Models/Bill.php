<?php

namespace App\Modules\BillManagement\Models;

use App\Modules\Authentication\Models\User;
use App\Modules\Portfolio\Models\Portfolio;
use App\Modules\Property\Models\Property;
use App\Modules\TenantManagement\Models\Lease;
use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Bill extends Model
{
    use HasFactory, HasUuids, SoftDeletes;
    use Filterable;

    protected $table = 'bills';

    protected $fillable = [
        'uuid',
        'lease_id',
        'property_id',
        'user_id',
        'portfolio_id',
        'type',
        'status',
        'amount',
        'due_date',
        'issued_date',
        'description',
    ];

    protected $guarded = [];

    protected $casts = [
        'due_date' => 'datetime',
        'issue_date' => 'datetime',
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $searchableRelations = [
        'type',
        'status',
        'property.name',
        'property.type',
        'user.first_name',
        'user.last_name',
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

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }
    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
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