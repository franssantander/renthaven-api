<?php

namespace App\Modules\UserManagement\Actions;

use App\Modules\Authentication\Models\User;

class CreateUserAction
{
    public function execute(array $params)
    {
        return User::create($params);
    }
}