<?php

namespace App\Policies;

use App\Models\Lease;
use App\Models\PropertyUnit;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class LeasePolicy
{
    /**
     * Determine whether the user can assign the given number of tenants to a property unit.
     */
    public function create(User $user, PropertyUnit $propertyUnit, int $incomingTenantCount): Response
    {
        $currentActiveCount = Lease::where('property_unit_id', $propertyUnit->id)
            ->where('is_active', true)
            ->count();

        if ($currentActiveCount + $incomingTenantCount > $propertyUnit->capacity) {
            return Response::deny("Unit capacity exceeded. This unit allows a maximum of {$propertyUnit->capacity} tenant(s); {$currentActiveCount} already assigned.");
        }

        return Response::allow();
    }
}
