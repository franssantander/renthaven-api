<?php

namespace App\Modules\Authentication\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Modules\Authentication\Actions\LogoutAction;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    public function __invoke(Request $request, LogoutAction $action)
    {
        return $action->execute($request->user());
    }
}