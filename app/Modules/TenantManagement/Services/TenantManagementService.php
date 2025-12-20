<?php

namespace App\Modules\TenantManagement\Services;

use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

class TenantManagementService
{
    public function __construct(
        private DatabaseManager $databaseManager,
        private CacheRepository $cache
    ) {}
}