<?php

namespace App\Modules\Property\DTO;

use App\Modules\Property\Models\Property;
use App\Services\DashboardMetric;
use Spatie\LaravelData\Data;

class DashboardPropertyData extends Data
{
    public function __construct(
        public int $total_properties,
        public int $total_available_properties,
        public int $total_inactive_properties,
    ) {
    }

    public static function fromService(DashboardMetric $metric): self
    {
        return new self(
            total_properties: $metric->getTotalCount(
                Property::class,
                ['is_active' => true]
            ),
            total_available_properties: $metric->getTotalCount(
                Property::class,
                ['is_active' => true, 'is_available' => true]
            ),
            total_inactive_properties: $metric->getTotalCount(
                Property::class,
                ['is_active' => false]
            ),
        );
    }
}