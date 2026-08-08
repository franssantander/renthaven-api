<?php

namespace Database\Seeders;

use App\Models\Lease;
use App\Services\Lease\LeaseService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class LeaseLifecycleDemoSeeder extends Seeder
{
    /**
     * Exercises the lease renewal and termination flows against real seeded
     * leases (via LeaseService, so this matches production rules exactly),
     * so both endpoints have realistic before/after data out of the box.
     */
    public function run(): void
    {
        $leaseService = app(LeaseService::class);

        $leases = Lease::where('is_active', true)
            ->orderBy('id')
            ->take(2)
            ->get();

        if ($leases->count() < 2) {
            $this->command->warn('Not enough active leases found to seed lease lifecycle demo data.');

            return;
        }

        [$renewalLease, $terminationLease] = $leases;

        // Scenario 1: a fixed-term lease nearing the end of its term gets renewed.
        $renewalLease->update([
            'end_date' => Carbon::now()->addDays(20)->toDateString(),
        ]);

        $leaseService->renewLease(
            $renewalLease,
            Carbon::now()->addMonths(12)->toDateString(),
        );

        $this->command->info("Renewed lease ID {$renewalLease->id} — extended 12 months.");

        // Scenario 2: a tenant moves out permanently; the deposit is settled
        // with a couple of standard move-out deductions.
        $terminationLease->update(['security_deposit' => $terminationLease->propertyUnit->rent_price * 2]);

        $leaseService->terminateLease(
            $terminationLease,
            Carbon::now()->subDays(2)->toDateString(),
            [
                ['description' => 'Cleaning fee', 'amount' => 500],
                ['description' => 'Minor wall repair', 'amount' => 1200],
            ],
            'Tenant moved out at end of lease term.',
        );

        $this->command->info("Terminated lease ID {$terminationLease->id} — deposit settled with deductions.");
    }
}
