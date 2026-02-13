<?php

namespace App\Modules\RoomManagement\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RoomManagementController extends Controller
{
    public function index(Request $request)
    {
        $data = "";
        return $this->success($data, "Successfully retrieved room management data.");
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