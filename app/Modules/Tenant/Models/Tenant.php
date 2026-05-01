<?php

namespace App\Modules\Tenant\Models;

use Modules\Tenant\Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Tenant extends Model
{

    use HasFactory;
    protected $table = 'tenants';

    protected $fillable = [
        'uuid',
        'name',
        'url',
        'email',
        'contact_number',
        'logo',
        'settings',
        'status',
    ];

    protected static function newFactory()
    {
        return TenantFactory::new();
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
}
