<?php

namespace App\Modules\Portfolio\Models;

use App\Traits\HasMultiTenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Database\Factories\PortfolioFactory;

class Portfolio extends Model
{
    use HasFactory, HasMultiTenantScope, SoftDeletes;

    protected $table = 'portfolios';

    protected $fillable = [
        'name',
        'phone_number',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function newFactory()
    {
        return PortfolioFactory::new();
    }
}