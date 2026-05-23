<?php

namespace App\Modules\RenterManagement\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Modules\RenterManagement\Actions\AddRenterAction;
use App\Modules\RenterManagement\Actions\AddRenterOnLeaseAction;
use App\Modules\RenterManagement\Actions\DeleteRenterAction;
use App\Modules\RenterManagement\Actions\GetAwaitingRentersAction;
use App\Modules\RenterManagement\Actions\GetRenterDetailsAction;
use App\Modules\RenterManagement\Actions\GetRenterListAction;
use App\Modules\RenterManagement\Actions\ChangeRenterProperty;
use App\Modules\RenterManagement\Actions\UpdateRenterDetails;
use App\Modules\RenterManagement\DTO\DashboardData;
use App\Modules\RenterManagement\DTO\LeaseData;
use App\Modules\RenterManagement\DTO\LeaseDetailsData;
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
        return $this->success(LeaseData::collect($data), "Leases retrieved successfully");
    }

    public function getAwaitingRenters(Request $request, GetAwaitingRentersAction $action)
    {
        $data = $action->execute($request->all());
        return $this->success($data, "Awaiting renters retrieved successfully");
    }

    public function store(AddRenterRequest $request, AddRenterAction $action)
    {
        $action->execute($request->validated());
        return $this->success(null, "Tenant added to lease successfully");
    }

    //todo maybe some use cases require a separate endpoint for adding a renter user to the system without assigning them to a lease immediately. If so, we can implement that here.
    public function addRenterOnLease(AddRenterOnLeaseRequest $request, AddRenterOnLeaseAction $action)
    {
        $action->execute($request->validated());
        return $this->success(null, 'Tenant created successfully');
    }

    public function show(Lease $lease, GetRenterDetailsAction $action)
    {
        $data = $action->execute($lease);
        return $this->success(LeaseDetailsData::from($data), "Lease details retrieved successfully");
    }

    public function update(Lease $lease, Request $request, UpdateRenterDetails $action)
    {
        $data = $action->execute($lease, $request->all());
        return $this->success($data, "Lease updated successfully");
    }

    public function changeRenterProperty(Lease $lease, Request $request, ChangeRenterProperty $action)
    {
        $data = $action->execute($lease, $request->all());
        return $this->success($data, "Tenant updated successfully");
    }

    public function destroy(Lease $lease, DeleteRenterAction $action)
    {
        $action->execute($lease);
        return $this->success(null, 'Lease deleted successfuly');
    }
}