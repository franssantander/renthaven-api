<?php

namespace App\Services;

class DashboardMetric
{

    public function getTotalCount(string $modelClass, $conditions = [])
    {
        $query = $modelClass::query();
        if (method_exists($modelClass, 'forUser')) {
            $query->forUser(auth()->user());
        }
        foreach ($conditions as $field => $value) {
            if (is_int($field)) {
                $query->where($value, true);
            } else {
                $query->where($field, $value);
            }
        }

        return $query->count();
    }
}
