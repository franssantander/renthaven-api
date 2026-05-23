<?php

namespace App\Modules\RenterManagement\Actions;

use App\Modules\RenterManagement\Models\RenterUser;
use Illuminate\Support\Facades\DB;

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
            $user = RenterUser::create($params);

            if (!empty($params['property_uuid'])) {

                $leaseParams = [
                    'property_id' => $params['property_uuid'],
                    'renter_user_ids' => [$user->uuid],
                    'start_date' => $params['start_date'] ?? now()->toDateString(),
                ];
                $this->addRenterOnLeaseAction->execute($leaseParams);
            }
            return $user->load('activeLease.property');
        });
    }
}