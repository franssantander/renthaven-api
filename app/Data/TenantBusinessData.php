<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class TenantBusinessData extends Data
{
    public function __construct(
        public string $name,
        public string $email,
    ) {
    }
}