<?php

namespace App\Modules\RoomManagement\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Modules\Property\Models\Property;
use App\Modules\RoomManagement\Actions\DeleteTenantFromPropertyAction;
use App\Modules\RoomManagement\Actions\GetPropertyRoomDetailsAction;
use App\Modules\RoomManagement\Actions\GetPropertyRoomListAction;
use App\Modules\RoomManagement\Actions\MoveTenantOnProperty;
use App\Modules\RoomManagement\Http\Requests\DeleteTenantFromPropertyRequest;
use App\Modules\RoomManagement\Http\Requests\MoveTenantRequest;
use App\Modules\TenantManagement\Models\Lease;
use Illuminate\Http\Request;

class RoomManagementController extends Controller
{
    public function index(Request $request, GetPropertyRoomListAction $action)
    {
        $data = $action->execute($request->all());
        return $this->success($data, "Successfully retrieved room management data.");
    }

    public function store()
    {
        //
    }

    public function show(Property $room, GetPropertyRoomDetailsAction $action)
    {
        $data = $action->execute($room);
        return $this->success($data, "Successfully retrieved property room details data.");
    }

    //* Move tenant to other property/room
    public function update(MoveTenantRequest $request, Lease $room, MoveTenantOnProperty $action)
    {
        $data = $action->execute($request->validated(), $room);
        return $this->success($data, 'Successfully moved tenant on property.');
    }

    public function destroy(DeleteTenantFromPropertyRequest $request, DeleteTenantFromPropertyAction $action)
    {
        $action->execute($request->validated());
        return $this->success(true, 'Successfully removed tenant from property.');
    }
}