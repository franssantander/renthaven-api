<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\MapInputName;

class RoleData extends Data
{
    public function __construct(
        public string $uuid,
        #[MapInputName('name')]
        public string $role_name,
        public string $slug,
    ) {
    }
}