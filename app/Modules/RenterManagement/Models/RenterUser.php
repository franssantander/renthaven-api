<?php

namespace App\Modules\RenterManagement\Models;

use App\Modules\Tenant\Models\Tenant;
use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Passport\HasApiTokens;
use Modules\RenterManagement\Database\Factories\RenterFactory;

class RenterUser extends Authenticatable
{
    use HasUuids, HasFactory, SoftDeletes, Filterable, HasApiTokens, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'first_name',
        'last_name',
        'username',
        'email',
        'password',
        'age',
        'phone_number',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /**
     * Relationships
     */

    /**
     * Every renter belongs to one specific Business (Tenant).
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Accessors & Mutators
     */

    /**
     * Replicating your User name attribute for consistency.
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn(mixed $value, array $attributes) => $attributes['first_name'] . ' ' . $attributes['last_name']
        );
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    protected static function booted()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    protected static function newFactory()
    {
        return RenterFactory::new();
    }

    public function leases(): HasMany
    {
        return $this->hasMany(Lease::class, 'renter_user_id');
    }

    public function activeLease(): HasOne
    {
        return $this->hasOne(Lease::class, 'renter_user_id')->where('is_active', true);
    }
}
