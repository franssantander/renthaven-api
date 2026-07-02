<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\HasHasPublicUuidTrait;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;

#[Fillable(['role_id', 'tenant_business_id', 'full_name', 'email', 'phone', 'username', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements OAuthenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasHasPublicUuidTrait;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function findForPassport(string $username): ?self
    {
        return $this->where('username', $username)
            ->orWhere('email', $username)
            ->first();
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function tenantBusiness(): BelongsTo
    {
        return $this->belongsTo(TenantBusiness::class);
    }

}
