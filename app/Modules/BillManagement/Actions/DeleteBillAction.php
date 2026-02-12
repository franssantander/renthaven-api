<?php

namespace App\Modules\BillManagement\Actions;

class DeleteBillAction
{
    public function execute($model)
    {
        return $model->delete();
    }
}