<?php

namespace App\Modules\Authentication\Actions;

use App\Modules\Authentication\Data\LoginData;
use App\Modules\Authentication\Models\User;

class RefreshTokenAction
{
    public function execute(User $user): LoginData
    {
        $user->token()->revoke();

        $tokenResult = $user->createToken('Personal Access Token');
        $newToken = $tokenResult->accessToken;

        return LoginData::fromModel($newToken, $user);
    }
}