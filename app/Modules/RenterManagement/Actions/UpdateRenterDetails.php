<?php

namespace App\Modules\RenterManagement\Actions;

use App\Modules\RenterManagement\Models\RenterUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UpdateRenterDetails
{
    public function __construct(
        protected UpdateRenterLeaseAction $updateRenterLeaseAction
    ) {
    }

    public function execute(RenterUser $user, array $params)
    {
        return DB::transaction(function () use ($user, $params) {

            if (isset($params['password'])) {
                $params['password'] = Hash::make($params['password']);
            }
            $user->update($params);

            $activeLease = $user->activeLease()->withTrashed()->first();

            if ($activeLease) {
                $leaseParams = [
                    'start_date' => $params['start_date'] ?? $activeLease->start_date,
                    'end_date' => $params['end_date'] ?? $activeLease->end_date,
                    'lease_type' => $params['lease_type'] ?? $activeLease->lease_type,
                    'unit_number' => $params['unit_number'] ?? $activeLease->unit_number,
                    'monthly_rent' => $params['monthly_rent'] ?? $activeLease->monthly_rent,
                    'is_active' => $params['is_active'] ?? $user->is_active, // Sync user status to lease
                ];

                $this->updateRenterLeaseAction->execute($activeLease, $leaseParams);
            }

            return $user->load('activeLease.property');
        });
    }
}