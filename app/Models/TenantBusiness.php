<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('tenant_businesses')]
#[Fillable(['name', 'email', 'password'])]
class TenantBusiness extends Model
{
    //
}
