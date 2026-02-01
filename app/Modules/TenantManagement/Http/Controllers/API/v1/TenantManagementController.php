<?php

namespace App\Modules\TenantManagement\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Modules\TenantManagement\Actions\AddTenantOnLeaseAction;
use App\Modules\TenantManagement\Actions\DeleteTenantAction;
use App\Modules\TenantManagement\Actions\GetTenantDetailsAction;
use App\Modules\TenantManagement\Actions\GetTenantListAction;
use App\Modules\TenantManagement\Actions\UpdateTenantAction;
use App\Modules\TenantManagement\Http\Requests\AddTenantOnLeaseRequest;
use App\Modules\TenantManagement\Models\Lease;
use Illuminate\Http\Request;

class TenantManagementController extends Controller
{
    public function index(Request $request, GetTenantListAction $action)
    {
        $data = $action->execute($request->all());
        return $this->success($data, 200);
    }

    public function store(AddTenantOnLeaseRequest $request, AddTenantOnLeaseAction $action)
    {
        $data = $action->execute($request->all());
        return $this->success($data, 200);
    }

    public function show(Lease $lease, GetTenantDetailsAction $action)
    {
        $data = $action->execute($lease);
        return $this->success($data, 200);
    }

    public function update(Lease $lease, Request $request, UpdateTenantAction $action)
    {
        $data = $action->execute($lease, $request->validated());
        return $this->success($data, 200);
    }

    public function destroy(Lease $lease, DeleteTenantAction $action)
    {
        $data = $action->execute($lease);
        return $this->success($data, 200);
    }
}