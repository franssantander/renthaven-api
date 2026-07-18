<?php

namespace App\Data\AuditLog;

use Spatie\LaravelData\Data;

class AuditActorData extends Data
{
    public function __construct(
        public ?string $uuid,
        public ?string $name,
        public ?string $email,
        public ?string $role,
    ) {}
}
