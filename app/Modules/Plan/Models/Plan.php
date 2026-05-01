<?php

namespace App\Modules\Plan\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Plan extends Model
{
    protected $table = 'plans';
    protected $fillable = [
        'uuid',
        'name',
        'description',
        'max_properties',
        'price',
        'features',
        'is_active'
    ];


    protected $casts = [
        'features' => 'array',
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
}
