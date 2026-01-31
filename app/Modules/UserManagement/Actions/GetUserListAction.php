<?php

namespace App\Modules\UserManagement\Actions;

use App\Modules\Authentication\Models\User;

class GetUserListAction
{
    public function execute($params)
    {
        return User::filter($params);
    }
}