<?php

namespace App\Modules\Property\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Modules\Property\Actions\CreatePropertyAction;
use App\Modules\Property\Actions\GetPropertiesListAction;
use App\Modules\Property\Http\Requests\CreatePropertyRequest;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    public function index(Request $request, GetPropertiesListAction $action)
    {
        return $action->execute($request->all());
    }

    public function store(CreatePropertyRequest $request, CreatePropertyAction $action)
    {
        return $action->execute($request->validated());
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