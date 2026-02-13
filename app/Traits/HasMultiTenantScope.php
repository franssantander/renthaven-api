<?php

namespace App\Traits;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids; 

trait HasMultiTenantScope
{
    use HasUuids; 

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    protected static function bootHasMultiTenantScope()
    {
        static::creating(function ($model) {
            if (Schema::hasColumn($model->getTable(), 'issued_date')) {
                $model->issued_date = $model->issued_date ?? now();
            }
        });
    }

    public function scopeForUser(Builder $query, $user)
    {
        if (!$user)
            return $query;
        if ($user->role->name === 'superadmin')
            return $query;
        if ($user->portfolio_id)
            return $query->where('portfolio_id', $user->portfolio_id);
        return $query->where('user_id', $user->id);
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where('uuid', $value)
            ->forUser(auth()->user())
            ->firstOrFail();
    }
}