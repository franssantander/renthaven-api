<?php

namespace App\Modules\UserManagement\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Modules\Authentication\Models\User;
use App\Modules\UserManagement\Actions\CreateUserAction;
use App\Modules\UserManagement\Actions\DeleteUserAction;
use App\Modules\UserManagement\Actions\GetUserDetailsAction;
use App\Modules\UserManagement\Actions\GetUserListAction;
use App\Modules\UserManagement\Actions\UpdateUserAction;
use App\Modules\UserManagement\Http\Requests\CreateUserRequest;
use App\Modules\UserManagement\Http\Requests\UpdateUserRequest;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    public function index(Request $request, GetUserListAction $action)
    {
        $data = $action->execute($request->all());
        return $this->success($data, 200);
    }

    public function store(CreateUserRequest $request, CreateUserAction $action)
    {
        $data = $action->execute($request->validated());
        return $this->success($data, 200);
    }

    public function show(User $user, GetUserDetailsAction $action)
    {
        $data = $action->execute($user);
        return $this->success($data, 200);
    }

    public function update(User $user, UpdateUserRequest $request, UpdateUserAction $action)
    {
        $data = $action->execute($user, $request->validated());
        return $this->success($data, 200);
    }

    public function destroy(User $user, DeleteUserAction $action)
    {
        $data = $action->execute($user);
        return $this->success($data, 200);
    }
}