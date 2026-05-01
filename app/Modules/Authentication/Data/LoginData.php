<?php

namespace App\Modules\Authentication\Data;

use App\Modules\Authentication\Models\User;
use App\Modules\RenterManagement\Models\Renter;
use Spatie\LaravelData\Data;

class LoginData extends Data
{
    public function __construct(
        public string $access_token,
        public UserData $user,
    ) {
    }

    public static function fromModel(string $token, User|Renter $user): self
    {
        return new self(
            access_token: $token,
            user: UserData::from($user)
        );
    }
}