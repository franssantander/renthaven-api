<?php

use Spatie\LaravelData\Data;


class LeaseData extends Data
{

    public function __construct(
        public string $id,
        public string $uuid,
        public string $start_date,
        public string $end_date,
        public bool $is_active,
        public string $created_at,
        public bool $is_active,
    ) {
    }
}