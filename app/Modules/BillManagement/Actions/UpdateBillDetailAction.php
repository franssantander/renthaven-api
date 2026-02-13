<?php

namespace App\Modules\BillManagement\Actions;


class UpdateBillDetailAction
{
    public function execute(array $params, $model)
    {
        if (isset($params['status']) && $params['status'] === 'paid') {
            $params['payment_date'] = $params['payment_date'] ?? now();
        }
        $model->update($params);
        return $model->fresh();
    }
}