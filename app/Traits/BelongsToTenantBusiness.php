<?php

namespace App\Traits;

use App\Enum\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait BelongsToTenantBusiness
{
    protected static function bootBelongsToTenantBusiness(): void
    {
        // Auto-fill tenant_business_id on create, unless already set explicitly
        static::creating(function ($model) {
            if (!$model->tenant_business_id && $user = Auth::user()) {
                $model->tenant_business_id = $user->tenant_business_id;
            }
        });

        // Auto-filter every query to the current user's tenant, unless they're super admin
        static::addGlobalScope('tenant_business', function (Builder $builder) {
            $user = Auth::user();

            if (!$user) {
                return; // e.g. console/queue context — no scoping applied
            }

            if ($user->role?->slug === Role::SUPER_ADMIN->value) {
                return; // super admin sees everything, unscoped
            }

            $builder->where($builder->getModel()->getTable() . '.tenant_business_id', $user->tenant_business_id);
        });
    }
}