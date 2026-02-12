<?php

namespace App\Modules\BillManagement\Actions;


class UpdateBillDetailAction
{
    public function execute(array $params, $model)
    {
        return $model->update($params);
    }
}