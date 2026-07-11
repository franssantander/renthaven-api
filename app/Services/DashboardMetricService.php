<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class DashboardMetricService
{
    /**
     * Generate a standardized dashboard metric widget.
     *
     * @param string $title The display title (e.g., 'Total Properties')
     * @param string $icon The frontend icon identifier (e.g., 'heroicons:home')
     * @param Builder $baseQuery The scoped Eloquent query (e.g., Property::where('tenant_id', $id))
     * @param int $days The time period to compare (default 30 days)
     * @param string $dateColumn The timestamp column to filter by
     * @return array
     */
    public function buildCountMetric(
        string $title,
        string $icon,
        Builder $baseQuery,
        int $days = 30,
        string $dateColumn = 'created_at'
    ): array {
        $now = Carbon::now();
        $startOfCurrentPeriod = (clone $now)->subDays($days);
        $startOfPreviousPeriod = (clone $startOfCurrentPeriod)->subDays($days);

        // 1. Get total all-time count (or you can scope this to just the current period if preferred)
        $totalValue = (clone $baseQuery)->count();

        // 2. Calculate Current Period (e.g., Last 30 Days)
        $currentPeriodCount = (clone $baseQuery)
            ->where($dateColumn, '>=', $startOfCurrentPeriod)
            ->count();

        // 3. Calculate Previous Period (e.g., 31 to 60 Days ago)
        $previousPeriodCount = (clone $baseQuery)
            ->whereBetween($dateColumn, [$startOfPreviousPeriod, $startOfCurrentPeriod])
            ->count();

        $trend = $this->calculateTrend($currentPeriodCount, $previousPeriodCount);

        return [
            'title' => $title,
            'icon'  => $icon,
            'value' => $totalValue, 
            'trend' => [
                'percentage'  => $trend['percentage'],
                'is_positive' => $trend['is_positive'],
                'is_neutral'  => $trend['is_neutral'],
                'label'       => $trend['label'] . " (vs last {$days} days)"
            ]
        ];
    }

    /**
     * Mathematical helper to safely calculate percentage change
     */
    private function calculateTrend(float $current, float $previous): array
    {
        if ($previous == 0) {
            $percentage = $current > 0 ? 100 : 0;
        } else {
            $percentage = (($current - $previous) / $previous) * 100;
        }

        $percentage = round($percentage, 1);

        return [
            'percentage'  => abs($percentage),
            'is_positive' => $percentage > 0,
            'is_neutral'  => $percentage === 0.0,
            'label'       => $percentage > 0 ? "+{$percentage}%" : "{$percentage}%",
        ];
    }
}