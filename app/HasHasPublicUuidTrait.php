<?php

namespace App;

use Illuminate\Support\Str;

trait HasHasPublicUuidTrait
{
    protected static function bootHasHasPublicUuidTrait(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
