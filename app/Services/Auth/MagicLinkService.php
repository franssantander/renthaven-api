<?php

namespace App\Services\Auth;

use App\Models\MagicLinkToken;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MagicLinkService
{
    /**
     * Issue a new magic-link token for the given user and return the plain token.
     */
    public function issueFor(User $user): string
    {
        $plainToken = Str::random(64);

        MagicLinkToken::create([
            'user_id'    => $user->id,
            'token'      => Hash::make($plainToken),
            'expires_at' => now()->addMinutes(15),
        ]);

        return $plainToken;
    }

    /**
     * Consume a plain magic-link token: validate, mark used, and return the owning user.
     */
    public function consume(string $plainToken): ?User
    {
        $candidates = MagicLinkToken::whereNull('used_at')
            ->where('expires_at', '>', now())
            ->get();

        foreach ($candidates as $candidate) {
            if (Hash::check($plainToken, $candidate->token)) {
                $candidate->update(['used_at' => now()]);

                return $candidate->user;
            }
        }

        return null;
    }
}
