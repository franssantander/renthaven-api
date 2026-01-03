<?php

namespace App\Modules\Authentication\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Modules\Authentication\Actions\LoginAction;
use App\Modules\Authentication\Http\Requests\LoginUserRequest;


class LoginController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(LoginUserRequest $request, LoginAction $action)
    {
        return $action->execute($request->validated());
    }
}