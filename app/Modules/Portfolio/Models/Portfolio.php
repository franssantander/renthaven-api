<?php

namespace App\Modules\Portfolio\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Database\Factories\PortfolioFactory;

class Portfolio extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'portfolios';

    protected $fillable = [
        'name',
        'phone_number',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    protected static function newFactory()
    {
        return PortfolioFactory::new();
    }
}