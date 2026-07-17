<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'token', 'expires_at', 'used_at'])]
#[Table('magic_link_tokens')]
class MagicLinkToken extends Model
{
    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'user_id'    => 'integer',
            'expires_at' => 'datetime',
            'used_at'    => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
