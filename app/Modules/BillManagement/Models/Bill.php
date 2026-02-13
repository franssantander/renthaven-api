<?php

namespace App\Modules\BillManagement\Models;

use App\Modules\Authentication\Models\User;
use App\Modules\Portfolio\Models\Portfolio;
use App\Modules\Property\Models\Property;
use App\Modules\TenantManagement\Models\Lease;
use App\Traits\Filterable;
use App\Traits\HasMultiTenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bill extends Model
{
    use HasMultiTenantScope, HasFactory, SoftDeletes;
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
        'payment_date',
        'payment_method',
        'payment_reference',
    ];

    protected $guarded = [];

    protected $casts = [
        'due_date' => 'datetime',
        'issued_date' => 'datetime',
        'payment_date' => 'datetime',
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
}