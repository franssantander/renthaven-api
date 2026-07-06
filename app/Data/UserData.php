<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\MapInputName;

class UserData extends Data
{
    public function __construct(
        public int $id,
        public string $full_name,
        public string $email,
        public string $username,
        public RoleData $role,
        #[MapInputName('tenant_business')]
        public ?TenantBusinessData $tenant_business,
        public ?array $permissions = null,
    ) {}
}