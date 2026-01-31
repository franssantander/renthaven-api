<?php

namespace App\Modules\TenantManagement\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Modules\TenantManagement\Actions\AddTenantOnLeaseAction;
use App\Modules\TenantManagement\Actions\GetTenantListAction;
use Illuminate\Http\Request;

class TenantManagementController extends Controller
{
    public function index(Request $request, GetTenantListAction $action)
    {
        $data = $action->execute($request->all());
        return $this->success($data, 200);
    }

    public function store(Request $request, AddTenantOnLeaseAction $action)
    {
        $data = $action->execute($request->all());
        return $this->success($data, 200);
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