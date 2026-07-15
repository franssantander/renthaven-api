<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['property_id', 'name', 'capacity', 'rent_price', 'status'])]
#[Table('property_units')]
class PropertyUnit extends Model
{

    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'property_id' => 'integer',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}