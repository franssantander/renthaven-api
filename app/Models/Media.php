<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Media extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'description',
        'uploaded_by',
        'model_id',
        'model_type',
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

    /**
     * RELATIONSHIP 1: The Polymorphic Link
     * Get the parent model (Property, User, Portfolio, etc.)
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }


    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}