<?php

namespace App\Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $table = 'tenants';

    protected $fillable = [
        'name',
        'url',
        'email',
        'contact_number',
        'logo',
        'settings',
        'status',
    ];
}
