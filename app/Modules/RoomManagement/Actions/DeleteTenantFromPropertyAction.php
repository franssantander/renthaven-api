<?php

namespace App\Modules\RoomManagement\Actions;

use Illuminate\Support\Facades\DB;

class DeleteTenantFromPropertyAction
{
    public function execute($model)
    {
        return DB::transaction(function () use ($model) {
            $property = $model->property;

            $model->update(['is_active' => false, 'end_date' => now()]);
            $model->delete();

            if($property){
                $property->updateAvailability();
            }
        });
    }
}