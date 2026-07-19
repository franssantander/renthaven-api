<?php

namespace Database\Seeders;

use App\Models\RefreshToken;
use App\Models\User;
use App\Services\Auth\TokenService;
use Illuminate\Database\Seeder;

class RefreshTokenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tokenService = app(TokenService::class);

        $users = User::take(3)->get();

        foreach ($users as $user) {
            $tokenService->issue($user);
            $this->command->info("[refresh-token:active]  {$user->email}");

            $tokenService->issue($user);
            RefreshToken::where('user_id', $user->id)->latest('id')->first()?->update(['revoked_at' => now()]);
            $this->command->info("[refresh-token:revoked] {$user->email}");

            $tokenService->issue($user);
            RefreshToken::where('user_id', $user->id)->latest('id')->first()?->update(['expires_at' => now()->subDay()]);
            $this->command->info("[refresh-token:expired] {$user->email}");
        }
    }
}
