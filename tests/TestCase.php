<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Migrations declare `uuid` columns with a MySQL-only `UUID()` DB
        // default (see e.g. database/migrations/*_create_plans_table.php).
        // Eloquent-created rows never hit that default (HasHasPublicUuidTrait
        // sets it beforehand), but several seeders insert via the raw query
        // builder and do rely on it. Register an equivalent function so
        // those inserts work against the sqlite test database too.
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::connection()->getPdo()->sqliteCreateFunction('UUID', fn () => (string) Str::uuid());
        }
    }
}
