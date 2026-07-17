<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class UuidResolver
{
    /**
     * Resolve a table's internal integer id from its public uuid.
     */
    public static function id(string $table, ?string $uuid): ?int
    {
        return $uuid === null ? null : DB::table($table)->where('uuid', $uuid)->value('id');
    }

    /**
     * Resolve multiple uuids to their internal integer ids in one query.
     *
     * @param  string[]  $uuids
     * @return int[]
     */
    public static function ids(string $table, array $uuids): array
    {
        return DB::table($table)->whereIn('uuid', $uuids)->pluck('id')->all();
    }
}
