<?php

namespace App\Modules\UserManagement\Actions;

class GetUserDetailsAction
{
    public function execute($user)
    {
        return $user->load(['role', 'portfolio']);
    }
}