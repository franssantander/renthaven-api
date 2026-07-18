<?php

namespace App\Models;

use App\Enum\PropertyUnitStatus;
use App\HasHasPublicUuidTrait;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['property_id', 'name', 'capacity', 'rent_price', 'status'])]
#[Table('property_units')]
class PropertyUnit extends Model
{

    use SoftDeletes, HasHasPublicUuidTrait;

    protected function casts(): array
    {
        return [
            'property_id' => 'integer',
            'status'      => PropertyUnitStatus::class,
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class, 'property_unit_amenity');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(PropertyAttachment::class, 'attachable')->orderBy('sort_order');
    }
}