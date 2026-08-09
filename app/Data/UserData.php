<?php

namespace App\Data;

use App\Enum\Status;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\MapInputName;

class UserData extends Data
{
    public function __construct(
        public int $id,
        public string $uuid,
        public string $first_name,
        public ?string $middle_name,
        public string $last_name,
        public string $full_name,
        public string $email,
        public string $username,
        public ?string $phone,
        public ?Status $status,
        public RoleData $role,
        #[MapInputName('tenant_business')]
        public ?TenantBusinessData $tenant_business,
        public ?array $permissions = null,
    ) {}
}