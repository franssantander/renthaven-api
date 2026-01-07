<?php

namespace App\Modules\Authentication\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Modules\Authentication\Actions\LogoutAction;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    public function __invoke(Request $request, LogoutAction $action)
    {
        $data = $action->execute($request->user());
        return $this->success($data, 'Logout Successfully');
    }
}