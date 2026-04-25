<?php

namespace App\Modules\Portfolio\DTO;

use Spatie\LaravelData\Data;

class PortfolioData extends Data
{
    public function __construct(
        public int $id,
        public string $uuid,
        public string $name,
        public string $phone_number,
        public ?string $email
    ) {
    }
}