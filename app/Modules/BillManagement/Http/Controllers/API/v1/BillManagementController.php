<?php

namespace App\Modules\BillManagement\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Modules\BillManagement\Actions\GetBillListAction;
use App\Modules\BillManagement\Actions\SendBillOnTenantAction;
use Illuminate\Http\Request;

class BillManagementController extends Controller
{
    public function index(Request $request, GetBillListAction $action)
    {
        $data = $action->execute($request->all());
        return $this->success($data, 'Successfully retrieved bill list data.');
    }

    public function store(Request $request, SendBillOnTenantAction $action)
    {
        $data = $action->execute($request->all());
        return $this->success($data, 'Successfully sent bill on tenant.');
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