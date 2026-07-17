<?php

namespace Database\Seeders;

use App\Models\MagicLinkToken;
use App\Models\User;
use App\Services\Auth\MagicLinkService;
use Illuminate\Database\Seeder;

class MagicLinkTokenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $magicLinkService = app(MagicLinkService::class);

        $renters = User::whereHas('renterProfile')->take(3)->get();

        foreach ($renters as $renter) {
            $validToken = $magicLinkService->issueFor($renter);
            $this->command->info("[magic-link:valid]   {$renter->email} -> {$validToken}");

            $usedToken = $magicLinkService->issueFor($renter);
            MagicLinkToken::where('user_id', $renter->id)->latest('id')->first()?->update(['used_at' => now()]);
            $this->command->info("[magic-link:used]    {$renter->email} -> {$usedToken}");

            $expiredToken = $magicLinkService->issueFor($renter);
            MagicLinkToken::where('user_id', $renter->id)->latest('id')->first()?->update(['expires_at' => now()->subMinutes(30)]);
            $this->command->info("[magic-link:expired] {$renter->email} -> {$expiredToken}");
        }
    }
}
