<?php

namespace App\Modules\RoomManagement\Actions;

use App\Modules\Property\Models\Property;

class GetPropertyRoomListAction
{
    public function execute(array $params)
    {
        return Property::forUser(auth()->user())
            ->with(['activeLeases.user'])
            ->filter($params);
    }
}