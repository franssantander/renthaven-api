<?php

namespace App\Data\Permission;

use Spatie\LaravelData\Data;

class PermissionActionData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
    ) {}
}