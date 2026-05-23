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
            // 1. Fetch Property and determine its math rules
            $property = Property::where('uuid', $params['property_id'])->firstOrFail();
            $renterUuids = (array) $params['renter_user_ids'];

            // Determine capacity based on business type
            $maxCapacity = $property->is_shared ? $property->pax : $property->total_units;

            // 2. Early Availability Check
            if (!$property->is_available || !$property->is_active) {
                throw ValidationException::withMessages([
                    'property_id' => 'This property is currently full or inactive.'
                ]);
            }

            // 3. Capacity Validation
            $currentOccupancy = Lease::where('property_id', $property->id)
                ->where('is_active', true)
                ->count();

            $requestedSpots = count($renterUuids);
            $remainingSpots = $maxCapacity - $currentOccupancy;

            if ($requestedSpots > $remainingSpots) {
                throw ValidationException::withMessages([
                    'renter_user_ids' => "Capacity exceeded. This property has only {$remainingSpots} spot(s) left."
                ]);
            }

            // 4. Process Renters
            $users = RenterUser::whereIn('uuid', $renterUuids)->get();
            $createdLeases = [];

            foreach ($renterUuids as $index => $uuid) {
                $user = $users->where('uuid', $uuid)->first();

                if (!$user) {
                    throw ValidationException::withMessages(["renter_user_ids.{$index}" => "User not found."]);
                }

                // Ensure they aren't already renting elsewhere
                $isAlreadyLeasing = Lease::where('renter_user_id', $user->id)
                    ->where('is_active', true)
                    ->exists();

                if ($isAlreadyLeasing) {
                    throw ValidationException::withMessages([
                        "renter_user_ids.{$index}" => "Renter {$user->first_name} already has an active lease."
                    ]);
                }

                $createdLeases[] = Lease::create([
                    'uuid' => (string) Str::uuid(),
                    'renter_user_id' => $user->id,
                    'property_id' => $property->id,
                    'tenant_id' => auth()->user()->tenant_id,
                    'monthly_rent' => $property->monthly_rent_price,
                    'unit_number' => $params['unit_number'] ?? null,
                    'start_date' => $params['start_date'],
                    'is_active' => true,
                ]);
            }

            // 5. Sync Property Stats
            // We update 'occupied' and 'is_available' in one go
            $newOccupancyCount = $currentOccupancy + count($createdLeases);

            $property->update([
                'occupied' => $newOccupancyCount,
                'is_available' => $newOccupancyCount < $maxCapacity
            ]);

            return $createdLeases;
        });
    }
}