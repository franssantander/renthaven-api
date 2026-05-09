<?php

namespace App\Modules\RenterManagement\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Modules\RenterManagement\Actions\AddRenterAction;
use App\Modules\RenterManagement\Actions\AddRenterOnLeaseAction;
use App\Modules\RenterManagement\Actions\DeleteRenterAction;
use App\Modules\RenterManagement\Actions\GetRenterDetailsAction;
use App\Modules\RenterManagement\Actions\GetRenterListAction;
use App\Modules\RenterManagement\Actions\UpdateRenterAction;
use App\Modules\RenterManagement\DTO\DashboardData;
use App\Modules\RenterManagement\Http\Requests\AddRenterOnLeaseRequest;
use App\Modules\RenterManagement\Http\Requests\AddRenterRequest;
use App\Modules\RenterManagement\Models\Lease;
use App\Services\DashboardMetric;
use Illuminate\Http\Request;

class RenterManagementController extends Controller
{

    public function __construct(protected DashboardMetric $dashboardMetric)
    {

    }

    public function dashboard()
    {
        $data = DashboardData::fromService($this->dashboardMetric);
        return $this->success($data, 'Dashboard tenant and payment metrics retrieved successfully.');
    }

    public function index(Request $request, GetRenterListAction $action)
    {
        $data = $action->execute($request->all());
        return $this->success($data, 200);
    }

    public function store(AddRenterRequest $request, AddRenterAction $action)
    {
        $data = $action->execute($request->validated());
        return $this->success($data, 'Tenant created successfully');
    }

    public function addTenantOnLease(AddRenterOnLeaseRequest $request, AddRenterOnLeaseAction $action)
    {
        $data = $action->execute($request->all());
        return $this->success($data, 200);
    }

    public function show(Lease $lease, GetRenterDetailsAction $action)
    {
        $data = $action->execute($lease);
        return $this->success($data, 200);
    }

    public function update(Lease $lease, Request $request, UpdateRenterAction $action)
    {
        $data = $action->execute($lease, $request->validated());
        return $this->success($data, 200);
    }

    public function destroy(Lease $lease, DeleteRenterAction $action)
    {
        $data = $action->execute($lease);
        return $this->success($data, 200);
    }
}