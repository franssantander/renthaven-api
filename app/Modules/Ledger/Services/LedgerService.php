<?php

namespace App\Modules\Ledger\Services;

use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

class LedgerService
{
    public function __construct(
        private DatabaseManager $databaseManager,
        private CacheRepository $cache
    ) {}
}