<?php

namespace App\Modules\RenterManagement\DTO;

use Spatie\LaravelData\Data;


class RenterUserData extends Data
{

    public function __construct(
        public int $id,
        public string $uuid,
        public int $tenant_id,
        public string $first_name,
        public string $last_name,
        public string $username,
        public ?string $email,
        public ?string $phone_number,
        public ?string $age,
        public bool $is_active,
        public string $created_at,
        public string $updated_at,
    ) {
    }
}