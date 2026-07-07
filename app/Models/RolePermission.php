<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('role_permission')]
class RolePermission extends Model
{
    //

    public function permission_action(): BelongsTo
    {
        return $this->belongsTo(PermissionAction::class);
    }

    public function permission_module(): BelongsTo
    {
        return $this->belongsTo(PermissionModule::class);
    }
}