<?php

namespace App\Modules\PropertyManagement\Services;

use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

class PropertyManagementService
{
    public function __construct(
        private DatabaseManager $databaseManager,
        private CacheRepository $cache
    ) {}
}