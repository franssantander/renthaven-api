<?php

namespace App\Data\Renter;

use Spatie\LaravelData\Data;

class RenterData extends Data
{
    public function __construct(
        public int $id,
        public string $uuid,
        public ?string $user_uuid,
        public string $first_name,
        public string $last_name,
        public string $email,
        public ?string $phone,
    ) {}
}
