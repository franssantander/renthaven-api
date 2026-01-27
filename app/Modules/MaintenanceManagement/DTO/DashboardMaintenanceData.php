<?php

namespace App\Modules\MaintenanceManagement\DTO;

use App\Modules\MaintenanceManagement\Models\MaintenanceProperty;
use App\Services\DashboardMetric;
use Spatie\LaravelData\Data;

class DashboardMaintenanceData extends Data
{
    public function __construct(
        public int $total_pending_requests,
        public int $total_in_progress_requests,
        public int $total_completed_requests,
    ) {
    }

    public static function fromService(DashboardMetric $metric): self
    {
        return new self(
            total_pending_requests: $metric->getTotalCount(MaintenanceProperty::class, ['status' => 'pending']),
            total_in_progress_requests: $metric->getTotalCount(MaintenanceProperty::class, ['status' => 'in_progress']),
            total_completed_requests: $metric->getTotalCount(MaintenanceProperty::class, ['status' => 'completed'])
        );
    }
}