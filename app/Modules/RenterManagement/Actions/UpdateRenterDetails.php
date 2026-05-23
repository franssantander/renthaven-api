<?php

namespace App\Modules\RenterManagement\Actions;

use App\Modules\RenterManagement\Models\RenterUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UpdateRenterDetails
{
    public function execute(RenterUser $renter, array $params)
    {
        return DB::transaction(function () use ($renter, $params) {
            if (!empty($params['password'])) {
                $params['password'] = Hash::make($params['password']);
            }

            $renter->update($params);

            $activeLease = $renter->activeLeases()->first();

            if ($activeLease) {
                $activeLease->update([
                    'start_date' => $params['start_date'] ?? $activeLease->start_date,
                    'monthly_rent' => $params['monthly_rent'] ?? $activeLease->monthly_rent,
                    'unit_number' => $params['unit_number'] ?? $activeLease->unit_number,
                ]);
            }

            return $renter->load('activeLeases.property');
        });
    }
}