<?php

namespace App\Modules\Property\Models;

use App\Modules\Authentication\Models\User;
use App\Modules\Portfolio\Models\Portfolio;
use App\Traits\Filterable;
use Database\Factories\PropertyFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Property extends Model
{
    use HasFactory, HasUuids, SoftDeletes;
    use Filterable;

    protected $table = 'properties';

    protected $fillable = [
        'name',
        'type',
        'description',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'zip_code',
        'number_of_rooms',
        'number_of_bathrooms',
        'area_sq_ft',
        'monthly_rent_price',
        'security_deposit',
        'is_available',
        'is_active',
        'has_parking',
        'allows_pets',
        'contact_email',
        'contact_phone',
        'portfolio_id',
        'created_by',
        'updated_by',
    ];

    protected $searchableRelations = [
        'portfolio.name',
        'createdBy.first_name',
        'createdBy.last_name',
        'createdBy.email',
        'createdBy.username',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'is_active' => 'boolean',
        'has_parking' => 'boolean',
        'allows_pets' => 'boolean',
        'monthly_rent_price' => 'decimal:2',
        'security_deposit' => 'decimal:2',
        'number_of_bathrooms' => 'decimal:1',
    ];

    protected static function newFactory()
    {
        return PropertyFactory::new();
    }

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

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
