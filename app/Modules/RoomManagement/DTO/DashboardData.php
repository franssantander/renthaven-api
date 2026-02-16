<?php

namespace App\Modules\RoomManagement\DTO;

use App\Modules\Property\Models\Property;
use App\Services\DashboardMetric;
use Spatie\LaravelData\Data;

class DashboardData extends Data
{
    public function __construct(
        public int $total_available_property,
        public int $total_occopied_property
    ) {
    }

    public static function fromService(DashboardMetric $metric): self
    {
        return new self(
            total_available_property: $metric->getTotalCount(
                Property::class,
                ['is_active' => true, 'is_available' => true]
            ),
            total_occopied_property: $metric->getTotalCount(
                Property::class,
                ['is_active' => true, 'is_available' => false]
            )
        );
    }
}
