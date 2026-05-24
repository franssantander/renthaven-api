<?php

namespace App\Modules\RenterManagement\Actions;

use App\Modules\RenterManagement\Models\RenterUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AddRenterAction
{
    public function __construct(
        protected AddRenterOnLeaseAction $addRenterOnLeaseAction
    ) {
    }

    public function execute(array $params)
    {
        return DB::transaction(function () use ($params) {
            $authUser = auth()->user();

            $params['tenant_id'] = $authUser->tenant_id;

            if (isset($params['password'])) {
                $params['password'] = Hash::make($params['password']);
            }

            $user = RenterUser::create($params);

            if (!empty($params['property_uuid'])) {
                $leaseParams = [
                    'property_id' => $params['property_uuid'],
                    'renter_user_ids' => [$user->uuid],
                    'start_date' => $params['start_date'] ?? now()->toDateString(),
                    'end_date' => $params['end_date'] ?? null,
                    'lease_type' => $params['lease_type'] ?? 'monthly',
                    'unit_number' => $params['unit_number'] ?? null,
                    'monthly_rent' => $params['monthly_rent'] ?? null,

                ];

                $this->addRenterOnLeaseAction->execute($leaseParams);
            }

            return $user->load('activeLease.property');
        });
    }
}