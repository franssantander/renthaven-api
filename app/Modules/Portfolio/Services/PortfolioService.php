<?php

namespace App\Modules\Portfolio\Services;

use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

class PortfolioService
{
    public function __construct(
        private DatabaseManager $databaseManager,
        private CacheRepository $cache
    ) {}
}