<?php

namespace App\Modules\BillManagement\Actions;

use App\Modules\BillManagement\Models\Bill;

class GetBillListAction
{
    public function execute(array $params)
    {
        return Bill::with(['property', 'lease', 'portfolio', 'user'])
            ->forUser(auth()->user())
            ->filter($params);
    }
}