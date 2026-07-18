<?php

namespace App\Models;

use App\HasHasPublicUuidTrait;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'attachable_type',
    'attachable_id',
    'disk',
    'path',
    'original_filename',
    'mime_type',
    'size',
    'caption',
    'sort_order',
    'uploaded_by',
])]
#[Table('property_attachments')]
class PropertyAttachment extends Model
{
    use SoftDeletes, HasHasPublicUuidTrait;

    protected $appends = ['url'];

    protected function casts(): array
    {
        return [
            'attachable_id' => 'integer',
            'size' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    protected function url(): Attribute
    {
        return Attribute::make(
            get: fn () => Storage::disk($this->disk)->url($this->path),
        );
    }
}
