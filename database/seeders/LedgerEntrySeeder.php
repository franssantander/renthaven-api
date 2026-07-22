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
                'status' => LedgerStatus::PAID,
                'paid_at' => Carbon::parse($entries->first()->due_date)->addDays(3),
                'paid_by' => $paidBy?->id,
            ]);

            $overdueEntries = $entries->slice(1);
            $submittedClaimEntry = $overdueEntries->first(
                fn (LedgerEntry $entry) => Carbon::parse($entry->due_date)->lt($today)
            );

            foreach ($overdueEntries as $entry) {
                if (Carbon::parse($entry->due_date)->lt($today)) {
                    $entry->update([
                        'status' => LedgerStatus::OVERDUE,
                        'reminder_sent_at' => Carbon::parse($entry->due_date)->addDays(2),
                    ]);
                }
            }

            // Leave one overdue entry as a renter-submitted claim awaiting admin
            // approval, so the confirm/reject flow has real data to test against.
            if ($submittedClaimEntry) {
                $submittedClaimEntry->update([
                    'status' => LedgerStatus::SUBMITTED,
                    'submitted_at' => Carbon::parse($submittedClaimEntry->due_date)->addDays(4),
                    'submitted_amount' => $submittedClaimEntry->amount,
                    'submission_reference' => 'ZELLE-'.strtoupper(fake()->bothify('##??##')),
                    'submission_notes' => 'Paid via Zelle, please confirm.',
                ]);
            }

            // generateUpcomingEntries() never creates a period until it has already
            // started, so a genuine "not yet due" pending row wouldn't otherwise exist.
            // Create the next upcoming period directly, for seed/testing purposes only.
            $latest = $entries->last();
            $propertyUnit = $lease->propertyUnit;
            $nextPeriodStart = Carbon::parse($latest->period_end)->addDay();

            LedgerEntry::create([
                'lease_id' => $lease->id,
                'renter_id' => $lease->renter_id,
                'property_unit_id' => $propertyUnit->id,
                'tenant_business_id' => $latest->tenant_business_id,
                'amount' => $propertyUnit->rent_price,
                'period_start' => $nextPeriodStart->toDateString(),
                'period_end' => $nextPeriodStart->copy()->addMonth()->subDay()->toDateString(),
                'due_date' => $nextPeriodStart->toDateString(),
                'status' => LedgerStatus::PENDING,
            ]);
        });

        $this->seedPartialPaymentAndPenaltyDemo();
    }

    /**
     * Guarantee a partially-paid entry and a penalty-applied entry exist,
     * independent of how many billing periods happened to have naturally
     * elapsed above — so both scenarios are always testable out of the box.
     */
    private function seedPartialPaymentAndPenaltyDemo(): void
    {
        $lease = Lease::where('is_active', true)
            ->with('propertyUnit.property.tenantBusiness')
            ->first();

        if (! $lease) {
            $this->command->warn('No active lease found to seed partial-payment/penalty demo entries.');

            return;
        }

        $propertyUnit = $lease->propertyUnit;
        $tenantBusinessId = $propertyUnit->property->tenant_business_id;

        $paidBy = User::whereHas('role', fn ($query) => $query->whereIn('slug', ['admin', 'staff']))
            ->where('tenant_business_id', $tenantBusinessId)
            ->first();

        // A back-dated period, well past any grace period, partially settled by the tenant.
        $partialEntry = LedgerEntry::create([
            'lease_id' => $lease->id,
            'renter_id' => $lease->renter_id,
            'property_unit_id' => $propertyUnit->id,
            'tenant_business_id' => $tenantBusinessId,
            'amount' => $propertyUnit->rent_price,
            'period_start' => Carbon::now()->subMonths(6)->startOfMonth()->toDateString(),
            'period_end' => Carbon::now()->subMonths(6)->endOfMonth()->toDateString(),
            'due_date' => Carbon::now()->subMonths(6)->startOfMonth()->toDateString(),
            'status' => LedgerStatus::OVERDUE,
        ]);

        if ($paidBy) {
            app(LedgerService::class)->markPaid(
                $partialEntry,
                $paidBy,
                'Partial cash payment received; balance still outstanding.',
                round((float) $propertyUnit->rent_price / 2, 2),
            );
        }

        $this->command->info("Seeded a partially-paid entry (ID {$partialEntry->id}) for lease ID {$lease->id}.");

        // Another back-dated period, left for the real overdue/penalty sweep to process.
        $penaltyEntry = LedgerEntry::create([
            'lease_id' => $lease->id,
            'renter_id' => $lease->renter_id,
            'property_unit_id' => $propertyUnit->id,
            'tenant_business_id' => $tenantBusinessId,
            'amount' => $propertyUnit->rent_price,
            'period_start' => Carbon::now()->subMonths(5)->startOfMonth()->toDateString(),
            'period_end' => Carbon::now()->subMonths(5)->endOfMonth()->toDateString(),
            'due_date' => Carbon::now()->subMonths(5)->startOfMonth()->toDateString(),
            'status' => LedgerStatus::PENDING,
        ]);

        app(LedgerService::class)->flagOverdueAndNotify();

        $this->command->info("Seeded an entry (ID {$penaltyEntry->id}) for the overdue/penalty sweep to flag for lease ID {$lease->id}.");
    }
}
