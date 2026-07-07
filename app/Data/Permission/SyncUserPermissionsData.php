<?php

namespace App\Data\Permission;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\ArrayType;

class SyncUserPermissionsData extends Data
{
    public function __construct(
        #[Required, Exists('users', 'uuid')]
        public string $user_id, // Contains the UUID string

        /** @var array<int, UserModulePermissionInputData> */
        #[Required, ArrayType]
        public array $permissions,
    ) {}
}

class UserModulePermissionInputData extends Data
{
    public function __construct(
        #[Required, Exists('permission_modules', 'uuid')]
        public string $module_uuid,

        /** @var array<int, string> */
        #[Required, ArrayType]
        public array $action_uuids,
    ) {}
}