<?php

namespace App\Modules\TenantManagement\DTO;

use App\Modules\BillManagement\Models\Bill;
use App\Modules\TenantManagement\Models\Lease;
use App\Services\DashboardMetric;
use Spatie\LaravelData\Data;

class DashboardData extends Data
{
    public function __construct(
        public string $total_pending_payments,
        public string $total_active_tenants,
        public string $total_unpaid,
        public string $total_overdue_payments
    ) {
    }

    public static function fromService(DashboardMetric $metric): self
    {
        // return new self(
        //     total_pending_payments: $metric->getTotalCount(
        //         Bill::class,
        //         ['status' => 'pending']
        //     ),
        //     total_active_tenants: $metric->getTotalCount(Lease::class, ['is_active' => 1]),
        //     total_unpaid: $metric->getTotalCount(Bill::class, ['status' => 'unpaid']),
        //     total_overdue_payments: $metric->getTotalCount(Bill::class, ['status' => 'overdue']),
        // );
    }
}