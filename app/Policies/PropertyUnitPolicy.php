<?php

namespace App\Policies;

use App\Models\PropertyUnit;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PropertyUnitPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function create(User $user, int $incomingCount = 1): Response
    {
        $tenant = $user->tenantBusiness;

        if (!$tenant || !$tenant->plan) {
            return Response::deny('Your account is not associated with an active subscription plan.');
        }

        $plan = $tenant->plan;

        $currentUnitCount = PropertyUnit::whereHas('property', function ($query) use ($tenant) {
            $query->where('tenant_business_id', $tenant->id);
        })->count();

        if ($plan->max_units > 0 && $currentUnitCount + $incomingCount > $plan->max_units) {
            return Response::deny("Plan limit reached. Your current plan allows a maximum of {$plan->max_units} units.");
        }

        return Response::allow();
    }
}