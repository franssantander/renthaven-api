<?php

namespace App\Modules\MaintenanceManagement\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Modules\MaintenanceManagement\Actions\CreateMaintenanceAction;
use App\Modules\MaintenanceManagement\Actions\DeleteMaintenanceAction;
use App\Modules\MaintenanceManagement\Actions\GetMaintenanceDetailAction;
use App\Modules\MaintenanceManagement\Actions\GetMaintenanceListAction;
use App\Modules\MaintenanceManagement\Actions\UpdateMaintenanceDetailAction;
use App\Modules\MaintenanceManagement\DTO\DashboardMaintenanceData;
use App\Modules\MaintenanceManagement\Http\Requests\CreateMaintenanceRequest;
use App\Modules\MaintenanceManagement\Http\Requests\UpdateMaintenanceRequest;
use App\Modules\MaintenanceManagement\Models\MaintenanceProperty;
use App\Services\DashboardMetric;
use Illuminate\Http\Request;

class MaintenanceManagementController extends Controller
{

    public function __construct(protected DashboardMetric $dashboardMetric)
    {
    }

    public function dashboard()
    {
        $data = DashboardMaintenanceData::fromService($this->dashboardMetric);
        return $this->success($data, 'Dashboard maintenance metrics retrieved successfully');
    }
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

    public function show(MaintenanceProperty $maintenanceProperty, GetMaintenanceDetailAction $action)
    {
        $data = $action->execute($maintenanceProperty);
        return $this->success($data, 'Maintenance property detail retrieved successfully');
    }

    public function update(UpdateMaintenanceRequest $request, MaintenanceProperty $maintenanceProperty, UpdateMaintenanceDetailAction $action)
    {
        $data = $action->execute($request->validated(), $maintenanceProperty);
        return $this->success($data, 'Maintenance property updated successfully');
    }

    public function destroy(MaintenanceProperty $maintenanceProperty, DeleteMaintenanceAction $action)
    {
        $data = $action->execute($maintenanceProperty);
        return $this->success($data, 'Maintenance property deleted successfully');
    }
}