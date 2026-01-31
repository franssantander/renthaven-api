<?php

namespace App\Modules\UserManagement\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Modules\UserManagement\Actions\CreateUserAction;
use App\Modules\UserManagement\Http\Requests\CreateUserRequest;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    public function index()
    {
        //
    }

    public function store(CreateUserRequest $request, CreateUserAction $action)
    {
        $data = $action->execute($request->validated());
        return $this->success($data, 200);
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