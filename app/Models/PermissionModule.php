<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('permission_modules')]
#[Fillable('name', 'slug', 'description')]
class PermissionModule extends Model
{
    //
}