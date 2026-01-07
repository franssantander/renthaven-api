<?php

namespace App\Modules\Authentication\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Modules\Authentication\Actions\RefreshTokenAction;
use Illuminate\Http\Request;

class RefreshTokenController extends Controller
{
    public function __invoke(Request $request, RefreshTokenAction $action)
    {
        $data = $action->execute($request->user());
        return $this->success($data, 'Refresh Token Successfully.');
    }
}