<?php

namespace App\Data\PropertyAttachment;

use Spatie\LaravelData\Data;

class PropertyAttachmentData extends Data
{
    public function __construct(
        public int $id,
        public string $uuid,
        public string $url,
        public string $original_filename,
        public ?string $caption,
        public int $sort_order,
    ) {}
}
