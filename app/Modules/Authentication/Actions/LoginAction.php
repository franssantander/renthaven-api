<?php

namespace App\Modules\Authentication\Actions;
use App\Modules\Authentication\Models\User;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class LoginAction
{
    public function execute($params)
    {
        $user = User::where('username', $params['username'])
            ->where('is_active', true)
            ->first();

        if (!$user) {
            abort(Response::HTTP_BAD_REQUEST, 'The account does not exist or is inactive.');
        }

        if (!Hash::check($params['password'], $user->password)) {
            abort(Response::HTTP_UNAUTHORIZED, 'Invalid credentials provided.');
        }

        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->accessToken;

        return [
            'access_token' => $token,
            'user' => [
                'username' => $user->username,
                'email' => $user->email,
                'name' => $user->first_name . ' ' . $user->last_name,
            ]
        ];
    }
}