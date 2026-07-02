<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('plans')]
#[Fillable(['name', 'slug', 'description', 'price', 'max_properties', 'max_units'])]
class Plan extends Model
{

    protected $casts = [
        "price" => 'decimal:2',
        "created_at" => 'datetime',
        "updated_at" => 'datetime'
    ];

}
