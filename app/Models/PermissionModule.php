<?php

namespace App\Models;

use App\HasHasPublicUuidTrait;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Table('permission_modules')]
#[Fillable('name', 'slug', 'description')]
class PermissionModule extends Model
{
    use HasHasPublicUuidTrait;

    public function actions(): BelongsToMany
    {
        return $this->belongsToMany(PermissionAction::class, 'permission_module_action');
    }
}