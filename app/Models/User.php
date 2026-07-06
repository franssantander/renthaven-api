<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Enum\StatusEnum;
use App\HasHasPublicUuidTrait;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;

#[Fillable(['role_id', 'tenant_business_id', 'full_name', 'email', 'phone', 'username', 'password', 'status'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements OAuthenticatable, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasHasPublicUuidTrait, SoftDeletes;

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
            'status' => StatusEnum::class
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

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification());
    }

    public function hasPermission(string $moduleSlug, string $actionCode): bool
    {
        // Super Admins always get a free pass to everything
        if ($this->role->slug === 'super_admin') {
            return true;
        }

        return DB::table('role_permissions')
            ->join('permission_modules', 'role_permissions.permission_module_id', '=', 'permission_modules.id')
            ->join('permission_actions', 'role_permissions.permission_action_id', '=', 'permission_actions.id')
            ->where('role_permissions.role_id', $this->role_id)
            ->where('permission_modules.slug', $moduleSlug)
            ->where('permission_actions.slug', $actionCode)
            ->exists();
    }

    public function getPermissionMatrix(): array
    {
        if ($this->relationLoaded('role') && $this->role->slug === 'super_admin') {
            $allModules = DB::table('permission_modules')->pluck('slug');
            $allActions = DB::table('permission_actions')->pluck('slug')->toArray();

            return $allModules->map(fn($slug) => [
                'module' => $slug,
                'actions' => $allActions
            ])->toArray();
        }

        return DB::table('role_permissions')
            ->join('permission_modules', 'role_permissions.permission_module_id', '=', 'permission_modules.id')
            ->join('permission_actions', 'role_permissions.permission_action_id', '=', 'permission_actions.id')
            ->where('role_permissions.role_id', $this->role_id)
            ->select('permission_modules.slug as module', 'permission_actions.slug as action')
            ->get()
            ->groupBy('module')
            ->map(fn($items, $moduleSlug) => [
                'module' => $moduleSlug,
                'actions' => $items->pluck('action')->toArray()
            ])
            ->values()
            ->toArray();
    }
}