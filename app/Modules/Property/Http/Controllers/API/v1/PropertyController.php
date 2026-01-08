<?php

namespace App\Modules\Property\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Modules\Property\Actions\GetPropertiesListAction;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    public function index(Request $request, GetPropertiesListAction $action)
    {
        return $action->execute($request->all());
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