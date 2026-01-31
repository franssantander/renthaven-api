<?php

namespace App\Modules\UserManagement\Actions;

class DeleteUserAction
{
    public function execute($user)
    {
        return $user->delete();
    }
}