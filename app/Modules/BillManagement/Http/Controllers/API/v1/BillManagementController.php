<?php

namespace App\Modules\BillManagement\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Modules\BillManagement\Actions\DeleteBillAction;
use App\Modules\BillManagement\Actions\GetBillDetailAction;
use App\Modules\BillManagement\Actions\GetBillListAction;
use App\Modules\BillManagement\Actions\GetTransactionList;
use App\Modules\BillManagement\Actions\SendBillOnTenantAction;
use App\Modules\BillManagement\Actions\UpdateBillDetailAction;
use App\Modules\BillManagement\Http\Requests\SendBillRequest;
use App\Modules\BillManagement\Http\Requests\UpdateBillDetailRequest;
use App\Modules\BillManagement\Models\Bill;
use Illuminate\Http\Request;

class BillManagementController extends Controller
{
    public function index(Request $request, GetBillListAction $action)
    {
        $data = $action->execute($request->all());
        return $this->success($data, 'Successfully retrieved bill list data.');
    }

    public function store(SendBillRequest $request, SendBillOnTenantAction $action)
    {
        $data = $action->execute($request->validated());
        return $this->success($data, 'Successfully sent bill on tenant.');
    }

    public function show(Bill $bill, GetBillDetailAction $action)
    {
        $data = $action->execute($bill);
        return $this->success($data, 'Successfully retrieved bill detail data.');
    }

    public function update(UpdateBillDetailRequest $request, Bill $bill, UpdateBillDetailAction $action)
    {
        $data = $action->execute($request->validated(), $bill);
        return $this->success($data, 'Successfully updated bill detail data.');
    }

    public function destroy(Bill $bill, DeleteBillAction $action)
    {
        $data = $action->execute($bill);
        return $this->success($data, 'Successfully deleted bill.');
    }

    public function getTransaction(Request $request, GetTransactionList $action)
    {
        $data = $action->execute($request->all());
        return $this->success($data, 'Successfully retrieved transaction list data.');
    }
}