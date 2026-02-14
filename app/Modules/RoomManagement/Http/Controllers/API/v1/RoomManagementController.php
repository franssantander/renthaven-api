<?php

namespace App\Modules\RoomManagement\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Modules\RoomManagement\Actions\GetPropertyRoomListAction;
use App\Modules\RoomManagement\Actions\MoveTenantOnProperty;
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

    public function show($id)
    {
        //
    }

    //* Move tenant to other property/room
    public function update(MoveTenantRequest $request, Lease $lease, MoveTenantOnProperty $action)
    {
        $data = $action->execute($request->validated(), $lease);
        return $this->success($data, 'Successfully moved tenant on property.');
    }

    public function destroy($id)
    {
        //
    }
}