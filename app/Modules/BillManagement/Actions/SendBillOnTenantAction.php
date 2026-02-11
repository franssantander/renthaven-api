<?php

namespace App\Modules\BillManagement\Actions;

use App\Modules\BillManagement\Models\Bill;
use App\Modules\TenantManagement\Models\Lease;

class SendBillOnTenantAction
{
    public function execute(array $params)
    {
        $lease = Lease::where('uuid', $params['lease_id'])->firstOrFail();

        $params['lease_id'] = $lease->id;
        $params['property_id'] = $lease->property_id;
        $params['user_id'] = $lease->user_id;
        $params['portfolio_id'] = $lease->portfolio_id;

        return Bill::create($params);
    }
}