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
            $table = $model->getTable();

            if (Schema::hasColumn($table, 'issued_date')) {
                $model->issued_date = $model->issued_date ?? now();
            }

            if (auth()->check()) {
                $user = auth()->user();

                if (Schema::hasColumn($table, 'portfolio_id') && empty($model->portfolio_id)) {
                    $model->portfolio_id = $user->portfolio_id;
                }

                if (Schema::hasColumn($table, 'created_by')) {
                    $model->created_by = auth()->id();
                }
            }
        });

        static::updating(function ($model) {
            if (auth()->check() && Schema::hasColumn($model->getTable(), 'updated_by')) {
                $model->updated_by = auth()->id();
            }
        });
    }

    public function scopeForUser(Builder $query, $user)
    {
        if (!$user || $user->role->name === 'superadmin')
            return $query;

        if ($user->portfolio_id)
            return $query->where('portfolio_id', $user->portfolio_id);

        if (Schema::hasColumn($this->getTable(), 'user_id'))
            return $query->where('user_id', $user->id);


        return $query;
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where('uuid', $value)
            ->forUser(auth()->user())
            ->firstOrFail();
    }
}