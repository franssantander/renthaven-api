<?php

namespace App\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * @return LengthAwarePaginator|Collection
 */

trait Filterable
{
    // Note: We removed the second argument $searchableColumns
    public function scopeFilter(Builder $query, array $params)
    {
        $term = $params['search'] ?? null;

        if ($term) {
            $fillable = $this->getFillable();
            $relations = property_exists($this, 'searchableRelations') ? $this->searchableRelations : [];

            $columnsToSearch = array_merge($fillable, $relations);

            $query->where(function (Builder $q) use ($columnsToSearch, $term) {
                foreach ($columnsToSearch as $column) {
                    // If it's a relationship (contains a dot)
                    if (str_contains($column, '.')) {
                        [$relation, $relationCol] = explode('.', $column, 2);
                        $q->orWhereHas($relation, function (Builder $relQuery) use ($relationCol, $term) {
                            $relQuery->where($relationCol, 'like', "%{$term}%");
                        });
                    }
                    // If it's a local column
                    else {
                        $q->orWhere($this->qualifyColumn($column), 'like', "%{$term}%");
                    }
                }
            });
        }

        // 2. DYNAMIC COLUMN FILTERING (?city=New York&status=active)
        // This loops through all params and filters if the key matches a column
        $ignoredKeys = ['page', 'per_page', 'sort_key', 'sort_by', 'search'];

        foreach ($params as $key => $value) {
            if (in_array($key, $ignoredKeys) || empty($value)) {
                continue;
            }

            // A. Handle Relations (e.g. ?portfolio.name=Luxury)
            if (str_contains($key, '.')) {
                [$relation, $col] = explode('.', $key, 2);
                // Simple check to ensure relation exists method on model to prevent crash
                if (method_exists($this, $relation)) {
                    $query->whereHas($relation, function ($q) use ($col, $value) {
                        $q->where($col, 'like', "%{$value}%");
                    });
                }
                continue;
            }

            // B. Handle Direct Columns (e.g. ?city=Manila)
            // Security: Only allow filtering on columns that are in $fillable
            // This prevents users from filtering on sensitive internal columns (like password/remember_token)
            if (in_array($key, $this->fillable)) {
                $query->where($key, 'like', "%{$value}%");
            }
        }

        // 3. SORTING logic (from previous answer) ...
        $sortKey = $params['sort_key'] ?? null;
        $sortBy = $params['sort_by'] ?? 'asc';

        if ($sortKey) {
            if (str_contains($sortKey, '.')) {
                $this->applyRelationshipSort($query, $sortKey, $sortBy);
            } else {
                $query->orderBy($sortKey, $sortBy);
            }
        } else {
            $query->latest();
        }

        $shouldPaginate = $params['per_page'] ?? null;
        if ($shouldPaginate) {
            return $query->paginate(
                $params['per_page'] ?? 15,
                ['*'],
                'page',
                $params['page'] ?? 1
            );
        }

        return $query->get();
    }

    /**
     * Helper to join related tables and sort by them.
     */
    protected function applyRelationshipSort(Builder $query, string $sortKey, string $direction): void
    {
        [$relationName, $column] = explode('.', $sortKey, 2);

        // Check if the model has this relationship defined
        if (!method_exists($this, $relationName)) {
            return;
        }

        // Get the relation instance (e.g., the BelongsTo object)
        $relation = $this->{$relationName}();

        // We currently only support BelongsTo for automatic joining in this example
        // (which covers your Portfolio, CreatedBy, UpdatedBy cases)
        if ($relation instanceof BelongsTo) {
            $relatedTable = $relation->getRelated()->getTable();
            $foreignKey = $relation->getForeignKeyName(); // e.g., portfolio_id
            $ownerKey = $relation->getOwnerKeyName();     // e.g., id

            // Perform a Left Join to ensure we don't lose records if the relation is missing
            // We use 'distinct' or select the main table fields to avoid id collisions
            if (!$query->getQuery()->joins) {
                $query->select($this->getTable() . '.*');
            }

            // Check if already joined to avoid duplicate joins
            $alreadyJoined = collect($query->getQuery()->joins)->contains('table', $relatedTable);

            if (!$alreadyJoined) {
                $query->leftJoin($relatedTable, $this->getTable() . '.' . $foreignKey, '=', $relatedTable . '.' . $ownerKey);
            }

            $query->orderBy("$relatedTable.$column", $direction);
        }
    }
}