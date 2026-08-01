<?php

namespace App\Data;

use App\Enum\Status;
use Spatie\LaravelData\Data;

class TenantBusinessData extends Data
{
    public function __construct(
        public string $uuid,
        public string $name,
        public string $email,
        public ?string $tin,
        public ?string $phone,
        public ?string $contact_person,
        public ?string $business_address,
        public ?string $logo_url,
        public ?Status $status,
    ) {}
}
