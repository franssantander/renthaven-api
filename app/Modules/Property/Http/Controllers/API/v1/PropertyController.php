<?php

namespace App\Modules\Property\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Modules\Property\Actions\CreatePropertyAction;
use App\Modules\Property\Actions\DeletePropertyAction;
use App\Modules\Property\Actions\GetPropertiesListAction;
use App\Modules\Property\Actions\GetPropertyDetailAction;
use App\Modules\Property\Actions\UpdatePropertyAction;
use App\Modules\Property\DTO\DashboardPropertyData;
use App\Modules\Property\DTO\PropertyData;
use App\Modules\Property\Http\Requests\CreatePropertyRequest;
use App\Modules\Property\Http\Requests\UpdatePropertyRequest;
use App\Modules\Property\Models\Property;
use App\Services\DashboardMetric;
use Illuminate\Http\Request;

class PropertyController extends Controller
{

    public function __construct(protected DashboardMetric $dashboardMetric)
    {
    }

    public function dashboard()
    {
        $data = DashboardPropertyData::fromService($this->dashboardMetric);
        return $this->success($data, 'Dashboard property metrics retrieved successfully');
    }

    public function index(Request $request, GetPropertiesListAction $action)
    {
        $data = $action->execute($request->all());
        return $this->success(PropertyData::collect($data), 'Properties data retrieved successfully');
    }

    public function store(CreatePropertyRequest $request, CreatePropertyAction $action)
    {
        $data = $action->execute($request->validated());
        return $this->success($data, 'Property created successfully');
    }

    public function show(Property $property, GetPropertyDetailAction $action)
    {
        $data = $action->execute($property);
        return $this->success(PropertyData::from($data), 'Property details retrieved successfully');
    }

    public function update(UpdatePropertyRequest $request, Property $property, UpdatePropertyAction $action)
    {
        $data = $action->execute($request->validated(), $property);
        return $this->success($data, 'Property updated successfully');
    }

    public function destroy(Property $property, DeletePropertyAction $action)
    {
        $action->execute($property);
        return $this->success(null, 'Property deleted successfully');
    }
}