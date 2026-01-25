<?php

namespace App\Modules\MaintenanceManagement\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Modules\MaintenanceManagement\Actions\GetMaintenanceListAction;
use Illuminate\Http\Request;

class MaintenanceManagementController extends Controller
{
    public function index(Request $request, GetMaintenanceListAction $action)
    {
        $data = $action->execute($request->all());
        return $this->success($data, 'Maintenance properties data retrieved successfully');
    }

    public function store(Request $request)
    {
        //
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