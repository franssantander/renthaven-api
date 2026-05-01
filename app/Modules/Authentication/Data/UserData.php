<?php

namespace App\Modules\Authentication\Data;

use App\Modules\Authentication\Models\User;
use Spatie\LaravelData\Data;

class UserData extends Data
{
    public function __construct(
        public string $username,
        public string $email,
        public string $name,
        public ?string $role,
    ) {
    }

    public static function fromModel(User $user): self
    {
        return new self(
            username: $user->username,
            email: $user->email,
            name: $user->name,
            role: $user->role->name,
        );
    }
}