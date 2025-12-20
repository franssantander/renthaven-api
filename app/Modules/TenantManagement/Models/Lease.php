<?php

namespace App\Modules\TenantManagement\Models;

use Database\Factories\LeaseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Lease extends Model
{
    use HasFactory, HasUuids, SoftDeletes;
    protected $guarded = [];

    protected $table = 'leases';

    protected $fillable = [
        'user_id',
        'property_id',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
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
}
