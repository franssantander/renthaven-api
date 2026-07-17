<?php

namespace App\Console\Commands;

use App\Services\Ledger\LedgerService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('ledger:process')]
#[Description('Generate upcoming rent ledger entries and flag/notify overdue ones')]
class ProcessLedgerCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(LedgerService $ledgerService): int
    {
        $generated = $ledgerService->generateUpcomingEntries();
        $this->info("Generated {$generated} ledger entr" . ($generated === 1 ? 'y' : 'ies') . '.');

        $flagged = $ledgerService->flagOverdueAndNotify();
        $this->info("Flagged {$flagged} entr" . ($flagged === 1 ? 'y' : 'ies') . ' as overdue and sent reminders.');

        return self::SUCCESS;
    }
}
