<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('permission_per_user')]
#[Fillable('user_id', 'permission_module_id', 'permission_action_id', 'is_active')]
class PermissionPerUser extends Model
{
    protected $casts = [
        'is_active' => 'boolean',
    ];
}