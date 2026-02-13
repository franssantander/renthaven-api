<?php

namespace App\Modules\BillManagement\Actions;

use App\Modules\BillManagement\Models\Bill;

class GetTransactionList
{
    public function execute(array $params)
    {
        return Bill::with(['lease', 'property', 'user', 'portfolio'])
            ->forUser(auth()->user())
            ->filter($params);
    }
}