<?php

namespace App\Modules\RoomManagement\Actions;

class GetPropertyRoomDetailsAction
{
    public function execute($model)
    {
        return $model->load(['activeLeases.user']);
    }
}