<?php

namespace App\Modules\RenterManagement\Actions;

use App\Modules\Property\Models\Property;
use App\Modules\RenterManagement\Models\Lease;
use App\Modules\RenterManagement\Models\RenterUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AddRenterOnLeaseAction
{
    public function execute(array $params)
    {
        return DB::transaction(function () use ($params) {
            $property = Property::where('uuid', $params['property_id'])->firstOrFail();
            $renterUuids = (array) $params['renter_user_ids'];

            if (!$property->is_available || !$property->is_active) {
                throw ValidationException::withMessages([
                    'property_id' => 'This property is currently unavailable for new leases.'
                ]);
            }

            $currentOccupancy = Lease::where('property_id', $property->id)
                ->where('is_active', true)
                ->count();

            if (($currentOccupancy + count($renterUuids)) > $property->pax) {
                throw ValidationException::withMessages([
                    'renter_user_ids' => "Capacity exceeded. Only " . ($property->pax - $currentOccupancy) . " spot(s) remaining."
                ]);
            }

            $users = RenterUser::whereIn('uuid', $renterUuids)->get();

            $createdLeases = [];
            foreach ($renterUuids as $index => $uuid) {
                $user = $users->where('uuid', $uuid)->first();

                if (!$user) {
                    throw ValidationException::withMessages(["renter_user_ids.{$index}" => "User not found."]);
                }

                $isAlreadyLeasing = Lease::where('renter_user_id', $user->id)
                    ->where('is_active', true)
                    ->exists();

                if ($isAlreadyLeasing) {
                    throw ValidationException::withMessages([
                        "renter_user_ids.{$index}" => "User {$user->first_name} already has an active lease."
                    ]);
                }

                $createdLeases[] = Lease::create([
                    'uuid' => Str::uuid(),
                    'renter_user_id' => $user->id,
                    'property_id' => $property->id,
                    'tenant_id' => auth()->user()->tenant_id,
                    'start_date' => $params['start_date'],
                    'is_active' => true,
                ]);
            }

            if (($currentOccupancy + count($createdLeases)) >= $property->pax) {
                $property->update(['is_available' => false]);
            }

            return $createdLeases;
        });
    }
}