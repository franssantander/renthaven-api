<?php

namespace Database\Seeders;

use App\Enum\LedgerStatus;
use App\Models\Lease;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Services\Ledger\LedgerService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class LedgerEntrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reuse the real generation logic so seed data matches production rules exactly.
        // generateUpcomingEntries() only advances ONE period per lease per call (that's
        // correct for the real daily scheduled command), so for leases that started in
        // the past we have to keep calling it until it stops producing new rows, in
        // order to backfill every elapsed period instead of just the first one.
        $ledgerService = app(LedgerService::class);
        $total = 0;

        do {
            $generated = $ledgerService->generateUpcomingEntries();
            $total += $generated;
        } while ($generated > 0);

        $this->command->info("Generated {$total} ledger entries via LedgerService.");

        $today = Carbon::today();

        Lease::where('is_active', true)->each(function (Lease $lease) use ($today) {
            $entries = LedgerEntry::where('lease_id', $lease->id)
                ->orderBy('period_start')
                ->get();

            if ($entries->isEmpty()) {
                return;
            }

            $paidBy = User::whereHas('role', function ($query) {
                $query->whereIn('slug', ['admin', 'staff']);
            })
                ->where('tenant_business_id', $entries->first()->tenant_business_id)
                ->first();

            $entries->first()->update([
                'status'  => LedgerStatus::PAID,
                'paid_at' => Carbon::parse($entries->first()->due_date)->addDays(3),
                'paid_by' => $paidBy?->id,
            ]);

            foreach ($entries->slice(1) as $entry) {
                if (Carbon::parse($entry->due_date)->lt($today)) {
                    $entry->update([
                        'status'           => LedgerStatus::OVERDUE,
                        'reminder_sent_at' => Carbon::parse($entry->due_date)->addDays(2),
                    ]);
                }
            }

            // generateUpcomingEntries() never creates a period until it has already
            // started, so a genuine "not yet due" pending row wouldn't otherwise exist.
            // Create the next upcoming period directly, for seed/testing purposes only.
            $latest = $entries->last();
            $propertyUnit = $lease->propertyUnit;
            $nextPeriodStart = Carbon::parse($latest->period_end)->addDay();

            LedgerEntry::create([
                'lease_id'           => $lease->id,
                'renter_id'          => $lease->renter_id,
                'property_unit_id'   => $propertyUnit->id,
                'tenant_business_id' => $latest->tenant_business_id,
                'amount'             => $propertyUnit->rent_price,
                'period_start'       => $nextPeriodStart->toDateString(),
                'period_end'         => $nextPeriodStart->copy()->addMonth()->subDay()->toDateString(),
                'due_date'           => $nextPeriodStart->toDateString(),
                'status'             => LedgerStatus::PENDING,
            ]);
        });
    }
}
