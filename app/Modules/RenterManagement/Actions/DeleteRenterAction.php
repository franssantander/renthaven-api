<?php

namespace App\Modules\RenterManagement\Actions;

use Illuminate\Support\Facades\DB;

class DeleteRenterAction
{
    public function execute($lease)
    {
        // return $lease->delete();
        return DB::transaction(function () use ($lease){
            return $lease->delete();
        });
    }
}