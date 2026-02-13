<?php

namespace App\Modules\RoomManagement\Services;

use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

class RoomManagementService
{
    public function __construct(
        private DatabaseManager $databaseManager,
        private CacheRepository $cache
    ) {}
}