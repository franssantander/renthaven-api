<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['tenant_business_id', 'name', 'address', 'type'])]
#[Table('property_units')]
class PropertyUnit extends Model
{

    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'tenant_business_id' => 'integer',
        ];
    }
}