<?php

namespace App\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

trait Filterable
{

    public function scopeFilter(Builder $query, array $params, array $searchableColumns = []): LengthAwarePaginator
    {
        $term = $params['search'] ?? null;
        if ($term && !empty($searchableColumns)) {
            $query->where(function (Builder $q) use ($searchableColumns, $term) {
                foreach ($searchableColumns as $column) {
                    if (str_contains($column, '.')) {
                        [$relation, $relationCol] = explode('.', $column, 2);

                        $q->orWhereHas($relation, function (Builder $relQuery) use ($relationCol, $term) {
                            $relQuery->where($relationCol, 'like', "%{$term}%");
                        });
                    } else {
                        $q->orWhere($column, 'like', "%{$term}%");
                    }
                }
            });
        }
        
        if (!empty($params['sort_key'])) {
            $query->orderBy($params['sort_key'], $params['sort_by'] ?? 'asc');
        } else {
            $query->latest();
        }

        return $query->paginate(
            $params['per_page'] ?? 15,
            ['*'],
            'page',
            $params['page'] ?? 1
        );
    }
}
