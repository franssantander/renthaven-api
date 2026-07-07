<?php

namespace App\Data\Permission;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\DataCollection;

class PermissionModuleData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,

        #[DataCollectionOf(PermissionActionData::class)]
        public ?array $actions
    ) {}
}