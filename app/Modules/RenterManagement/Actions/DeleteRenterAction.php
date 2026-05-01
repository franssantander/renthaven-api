<?php

namespace App\Modules\RenterManagement\Actions;

class DeleteRenterAction
{
    public function execute($lease)
    {
        return $lease->delete();
    }
}