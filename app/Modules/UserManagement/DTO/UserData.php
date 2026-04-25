<?php

namespace App\Modules\UserManagement\DTO;

use Spatie\LaravelData\Data;

class UserData extends Data
{
    public function __construct(
        public int $id,
        public string $uuid,
        public string $first_name,
        public string $last_name,
        public string $username,
        public string $email,
        public string $phone_number,
        public string $gender,
        public string $birth_date,
        public bool $is_active,
    ) {
    }
}