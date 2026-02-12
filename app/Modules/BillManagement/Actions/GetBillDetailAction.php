<?php

namespace App\Modules\BillManagement\Actions;


class GetBillDetailAction
{
    public function execute($model)
    {
        return $model->load(['property', 'lease', 'user']);
    }
}