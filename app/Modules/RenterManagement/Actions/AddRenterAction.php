<?php

namespace App\Modules\RenterManagement\Actions;

use App\Modules\RenterManagement\Models\RenterUser;
use Illuminate\Support\Facades\DB;

class AddRenterAction
{
    public function execute(array $params)
    {
        return DB::transaction(function () use ($params) {
            $authUser = auth()->user();
            $params['tenant_id'] = $authUser->tenant_id;
            $user = RenterUser::create($params);
            return $user;
        });
    }
}