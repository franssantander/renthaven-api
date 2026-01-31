<?php

namespace App\Modules\UserManagement\Actions;

class UpdateUserAction
{
    public function execute($user, array $params)
    {
        return $user->update($params);
    }
}