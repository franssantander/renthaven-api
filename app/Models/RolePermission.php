<?php

namespace App\Models;

use App\HasHasPublicUuidTrait;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('role_permissions')]
class RolePermission extends Model
{
    use HasHasPublicUuidTrait;

    public function permission_action(): BelongsTo
    {
        return $this->belongsTo(PermissionAction::class);
    }

    public function permission_module(): BelongsTo
    {
        return $this->belongsTo(PermissionModule::class);
    }
}