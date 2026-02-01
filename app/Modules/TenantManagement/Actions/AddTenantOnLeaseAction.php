<?php

namespace App\Modules\TenantManagement\Actions;

use App\Modules\Authentication\Models\User;
use App\Modules\Property\Models\Property;
use App\Modules\TenantManagement\Models\Lease;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AddTenantOnLeaseAction
{
    public function execute(array $params)
    {
        return DB::transaction(function () use ($params) {
            $user = User::where('uuid', $params['user_id'])->firstOrFail();
            $property = Property::where('uuid', $params['property_id'])->firstOrFail();

            $alreadyTenant = Lease::where('user_id', $user->id)
                ->where('property_id', $property->id)
                ->where('is_active', true)
                ->exists();

            if ($alreadyTenant) {
                throw ValidationException::withMessages([
                    'user_id' => 'This user is already an active tenant of the specified property.'
                ]);
            }

            if (!$property->is_available) {
                throw ValidationException::withMessages([
                    'property_id' => 'This property is currently unavailable or fully occupied.'
                ]);
            }

            $currentOccupancy = Lease::where('property_id', $property->id)
                ->where('is_active', true)
                ->count();

            if ($currentOccupancy >= $property->pax) {
                $property->update(['is_available' => false]);

                throw ValidationException::withMessages([
                    'property_id' => "This property has reached its max capacity of {$property->pax} tenants."
                ]);
            }

            $leaseData = [
                'user_id' => $user->id,
                'property_id' => $property->id,
                'portfolio_id' => auth()->user()->portfolio_id,
                'start_date' => $params['start_date'],
                'is_active' => true,
            ];

            $lease = Lease::create($leaseData);
            if (($currentOccupancy + 1) >= $property->pax) {
                $property->update(['is_available' => false]);
            }

            return $lease;
        });
    }
}