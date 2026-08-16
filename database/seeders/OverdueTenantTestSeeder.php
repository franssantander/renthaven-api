<?php

namespace Database\Seeders;

use App\Enum\LedgerStatus;
use App\Models\Lease;
use App\Models\LedgerEntry;
use App\Services\Ledger\LedgerService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Test-only: guarantees at least one tenant/ledger entry in a real OVERDUE
 * state, computed relative to whenever this seeder actually runs (not a
 * fixed calendar date), so flows like LedgerController::sendReminder() can
 * be exercised manually without depending on LedgerEntrySeeder's bulk
 * backdated-period math happening to leave something overdue.
 *
 * Deliberately NOT registered in DatabaseSeeder::run() — run it on demand:
 *   php artisan db:seed --class=OverdueTenantTestSeeder
 */
class OverdueTenantTestSeeder extends Seeder
{
    public function run(): void
    {
        $lease = Lease::where('is_active', true)
            ->whereHas('renter.user')
            ->with('propertyUnit.property.tenantBusiness', 'renter.user')
            ->first();

        if (! $lease) {
            $this->command->warn('No active lease with a linked user found to seed an overdue test entry.');

            return;
        }

        $tenantBusiness = $lease->propertyUnit->property->tenantBusiness;
        $graceDays = $tenantBusiness->grace_period_days ?? 5;

        // Comfortably clear the grace period regardless of this tenant's configured value.
        $dueDate = Carbon::today()->subDays($graceDays + 5);

        $entry = LedgerEntry::create([
            'lease_id' => $lease->id,
            'renter_id' => $lease->renter_id,
            'property_unit_id' => $lease->property_unit_id,
            'tenant_business_id' => $lease->propertyUnit->property->tenant_business_id,
            'amount' => $lease->propertyUnit->rent_price,
            'period_start' => $dueDate->copy()->startOfMonth(),
            'period_end' => $dueDate->copy()->endOfMonth(),
            'due_date' => $dueDate,
            'status' => LedgerStatus::PENDING,
        ]);

        // Reuse the real overdue-flagging logic (penalty + reminder) instead of hand-setting status.
        app(LedgerService::class)->flagOverdueAndNotify();

        $renter = $lease->renter;

        $this->command->info(
            "Seeded overdue ledger entry ID {$entry->id} (due {$dueDate->toDateString()}) ".
            "for renter \"{$renter->first_name} {$renter->last_name}\" <{$renter->user->email}>."
        );
    }
}
