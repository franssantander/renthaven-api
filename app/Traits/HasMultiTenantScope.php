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

                if (Schema::hasColumn($table, 'tenant_id') && empty($model->tenant_id)) {
                    $model->tenant_id = $user->tenant_id;
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
        if (!$user) {
            return $query;
        }

        $isSuperAdmin = $user->loadMissing('role')
            ? ($user->role?->code === 'super_admin')
            : false;


        if ($isSuperAdmin) {
            return $query;
        }

        if ($user->tenant_id) {
            return $query->where($this->getTable() . '.tenant_id', $user->tenant_id);
        }

        return $query;
    }
    public function resolveRouteBinding($value, $field = null)
    {
        $field = $field ?? 'uuid';

        return $this->where($field, $value)
            ->forUser(auth()->user())
            ->firstOrFail();
    }
}