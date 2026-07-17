<?php

namespace App\Models;

use App\HasHasPublicUuidTrait;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('roles')]
#[Fillable('name', 'slug')]
class Role extends Model
{
    use HasHasPublicUuidTrait;
}