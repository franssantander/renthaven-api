<?php

namespace App\Modules\Authentication\Data;

use Spatie\LaravelData\Data;

class RoleData extends Data
{
    public function __construct(
        public string $name
    ) {
    }
}