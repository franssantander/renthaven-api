<?php

namespace App\Services\Ledger;

use App\Enum\LeaseTermType;
use App\Enum\LedgerStatus;
use App\Models\Lease;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Notifications\OverdueRentReminderNotification;
use App\Services\Auth\MagicLinkService;
use Illuminate\Support\Carbon;

class LedgerService
{
    public function __construct(protected MagicLinkService $magicLinkService) {}

    /**
     * Generate the next billing-period ledger entry for every active lease
     * that doesn't already have one covering the upcoming period.
     */
    public function generateUpcomingEntries(): int
    {
        $today = Carbon::today();
        $generated = 0;

        Lease::where('is_active', true)
            ->with('propertyUnit.property')
            ->chunkById(100, function ($leases) use ($today, &$generated) {
                foreach ($leases as $lease) {
                    if ($this->generateNextEntryForLease($lease, $today)) {
                        $generated++;
                    }
                }
            });

        return $generated;
    }

    protected function generateNextEntryForLease(Lease $lease, Carbon $today): bool
    {
        $latestEntry = LedgerEntry::where('lease_id', $lease->id)
            ->orderByDesc('period_end')
            ->first();

        $periodStart = $latestEntry
            ? $latestEntry->period_end->copy()->addDay()
            : Carbon::parse($lease->start_date);

        // Only generate once the period has actually started (no far-future pre-generation).
        if ($periodStart->gt($today)) {
            return false;
        }

        if ($lease->term_type === LeaseTermType::FIXED_TERM && $lease->end_date && $periodStart->gt(Carbon::parse($lease->end_date))) {
            return false;
        }

        $periodEnd = $periodStart->copy()->addMonth()->subDay();
        $propertyUnit = $lease->propertyUnit;

        LedgerEntry::create([
            'lease_id'           => $lease->id,
            'renter_id'          => $lease->renter_id,
            'property_unit_id'   => $propertyUnit->id,
            'tenant_business_id' => $propertyUnit->property->tenant_business_id,
            'amount'             => $propertyUnit->rent_price,
            'period_start'       => $periodStart->toDateString(),
            'period_end'         => $periodEnd->toDateString(),
            'due_date'           => $periodStart->toDateString(),
            'status'             => LedgerStatus::PENDING,
        ]);

        return true;
    }

    /**
     * Flag pending entries past their due date as overdue and send a
     * reminder (once per entry) to the renter.
     */
    public function flagOverdueAndNotify(): int
    {
        $today = Carbon::today();
        $flagged = 0;

        LedgerEntry::where('status', LedgerStatus::PENDING)
            ->where('due_date', '<', $today)
            ->with('renter.user')
            ->chunkById(100, function ($entries) use (&$flagged) {
                foreach ($entries as $entry) {
                    $entry->update(['status' => LedgerStatus::OVERDUE]);
                    $this->sendReminder($entry);
                    $flagged++;
                }
            });

        return $flagged;
    }

    protected function sendReminder(LedgerEntry $entry): void
    {
        if ($entry->reminder_sent_at !== null) {
            return;
        }

        $user = $entry->renter?->user;

        if (!$user instanceof User) {
            return;
        }

        $token = $this->magicLinkService->issueFor($user);

        $user->notify(new OverdueRentReminderNotification($entry, $token));

        $entry->update(['reminder_sent_at' => now()]);
    }

    /**
     * Mark a ledger entry as paid.
     */
    public function markPaid(LedgerEntry $entry, User $staff, ?string $notes = null): LedgerEntry
    {
        $entry->update([
            'status'  => LedgerStatus::PAID,
            'paid_at' => now(),
            'paid_by' => $staff->id,
            'notes'   => $notes ?? $entry->notes,
        ]);

        return $entry;
    }
}
