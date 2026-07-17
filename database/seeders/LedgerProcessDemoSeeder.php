<?php

namespace Database\Seeders;

use App\Enum\LedgerStatus;
use App\Models\Lease;
use App\Models\LedgerEntry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class LedgerProcessDemoSeeder extends Seeder
{
    /**
     * Leaves two leases in an "unprocessed" state so `php artisan ledger:process`
     * has real work to do the first time it's run after seeding, instead of
     * everything already being fully caught up (which LedgerEntrySeeder produces).
     */
    public function run(): void
    {
        $demoLeases = Lease::where('is_active', true)
            ->whereHas('renter.user')
            ->with('propertyUnit.property')
            ->take(2)
            ->get();

        if ($demoLeases->count() < 2) {
            $this->command->warn('Not enough leases with a linked user found to seed ledger:process demo data.');
            return;
        }

        [$freshLease, $overdueLease] = $demoLeases;

        // Scenario 1: a lease with no ledger history at all, so `ledger:process`
        // generates its first entry live when you run the command.
        LedgerEntry::where('lease_id', $freshLease->id)->delete();
        $this->command->info("Reset ledger history for lease ID {$freshLease->id} — ready for ledger:process to generate its first entry.");

        // Scenario 2: a genuinely pending-but-overdue entry (not pre-flagged like
        // LedgerEntrySeeder does), so `ledger:process` flags it overdue and sends
        // a real reminder notification live.
        LedgerEntry::where('lease_id', $overdueLease->id)->delete();

        $propertyUnit = $overdueLease->propertyUnit;
        $periodStart = Carbon::today()->subDays(10);

        LedgerEntry::create([
            'lease_id'           => $overdueLease->id,
            'renter_id'          => $overdueLease->renter_id,
            'property_unit_id'   => $propertyUnit->id,
            'tenant_business_id' => $propertyUnit->property->tenant_business_id,
            'amount'             => $propertyUnit->rent_price,
            'period_start'       => $periodStart->toDateString(),
            'period_end'         => $periodStart->copy()->addMonth()->subDay()->toDateString(),
            'due_date'           => $periodStart->toDateString(),
            'status'             => LedgerStatus::PENDING,
        ]);

        $this->command->info("Seeded a pending-but-overdue entry for lease ID {$overdueLease->id} — ready for ledger:process to flag it and send a reminder.");
    }
}
