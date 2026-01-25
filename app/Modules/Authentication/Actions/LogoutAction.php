<?php

namespace App\Modules\Authentication\Actions;

class LogoutAction
{
    public function execute($user): bool
    {
        $token = $user->token();
        return $token && $token->revoke();
    }
}