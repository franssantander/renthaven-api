<?php

namespace App\Modules\MaintenanceManagement\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Modules\MaintenanceManagement\Actions\CreateMaintenanceAction;
use App\Modules\MaintenanceManagement\Actions\GetMaintenanceListAction;
use App\Modules\MaintenanceManagement\Http\Requests\CreateMaintenanceRequest;
use Illuminate\Http\Request;

class MaintenanceManagementController extends Controller
{
    public function index(Request $request, GetMaintenanceListAction $action)
    {
        $data = $action->execute($request->all());
        return $this->success($data, 'Maintenance properties data retrieved successfully');
    }

    public function store(CreateMaintenanceRequest $request, CreateMaintenanceAction $action)
    {
        $data = $action->execute($request->validated());
        return $this->success($data, 'Maintenance successfully created');
    }

    public function show($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function destroy($id)
    {
        //
    }
}