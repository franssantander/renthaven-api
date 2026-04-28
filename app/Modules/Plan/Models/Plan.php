<?php

namespace App\Modules\Plan\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $table = 'plans';
    protected $fillable = [
        'name',
        'description',
        'max_properties',
        'price',
        'features',
        'is_active'
    ];
}
