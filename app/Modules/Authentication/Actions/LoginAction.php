<?php

namespace App\Modules\Authentication\Actions;

use App\Modules\Authentication\Data\LoginData;
use App\Modules\Authentication\Models\User;
use App\Modules\RenterManagement\Models\Renter;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class LoginAction
{
    public function execute($params): LoginData
    {
        $user = User::with('role')
            ->where('username', $params['username'])
            ->where('is_active', true)
            ->first();

        if (!$user) {
            $user = Renter::where('username', $params['username'])
                ->where('is_active', true)
                ->first();
        }

        if (!$user) {
            abort(Response::HTTP_BAD_REQUEST, 'The account does not exist or is inactive.');
        }

        if (!Hash::check($params['password'], $user->password)) {
            abort(Response::HTTP_UNAUTHORIZED, 'Invalid credentials provided.');
        }

        $tokenResult = $user->createToken('Personal Access Token');

        return LoginData::fromModel($tokenResult->accessToken, $user);
    }
}