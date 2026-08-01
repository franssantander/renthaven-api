<?php

namespace App\Services\Ledger;

use App\Enum\LeaseTermType;
use App\Enum\LedgerStatus;
use App\Models\Lease;
use App\Models\LedgerEntry;
use App\Models\TenantBusiness;
use App\Models\User;
use App\Notifications\OverdueRentReminderNotification;
use App\Services\Auth\MagicLinkService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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

        $entry = LedgerEntry::create([
            'lease_id' => $lease->id,
            'renter_id' => $lease->renter_id,
            'property_unit_id' => $propertyUnit->id,
            'tenant_business_id' => $propertyUnit->property->tenant_business_id,
            'amount' => $propertyUnit->rent_price,
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'due_date' => $periodStart->toDateString(),
            'status' => LedgerStatus::PENDING,
        ]);

        if ($latestEntry === null && (float) $lease->advance_rent > 0 && $lease->advance_rent_applied_at === null) {
            $this->applyPayment($entry, (float) $lease->advance_rent, null, 'Applied from advance rent collected at move-in.');
            $lease->update(['advance_rent_applied_at' => now()]);
        }

        return true;
    }

    /**
     * Flag entries past their due date (and grace period) as overdue, apply a
     * one-time late-payment penalty per the tenant business's policy, and
     * send a reminder (once per entry) to the renter.
     */
    public function flagOverdueAndNotify(): int
    {
        $today = Carbon::today();
        $flagged = 0;

        LedgerEntry::whereIn('status', [LedgerStatus::PENDING, LedgerStatus::OVERDUE])
            ->where('due_date', '<', $today)
            ->with(['renter.user', 'tenantBusiness'])
            ->chunkById(100, function ($entries) use ($today, &$flagged) {
                foreach ($entries as $entry) {
                    $graceDays = $entry->tenantBusiness?->grace_period_days ?? 5;
                    $graceExpiry = Carbon::parse($entry->due_date)->addDays($graceDays);

                    if ($graceExpiry->gt($today)) {
                        // Still within the tenant business's grace period — leave it as-is.
                        continue;
                    }

                    $changed = false;

                    if ($entry->status === LedgerStatus::PENDING) {
                        $entry->status = LedgerStatus::OVERDUE;
                        $changed = true;
                    }

                    if ($entry->penalty_applied_at === null) {
                        $lateFeePercentage = (float) ($entry->tenantBusiness?->late_fee_percentage ?? 0);

                        if ($lateFeePercentage > 0) {
                            $entry->penalty_amount = round((float) $entry->amount * $lateFeePercentage / 100, 2);
                        }

                        $entry->penalty_applied_at = now();
                        $changed = true;
                    }

                    if ($changed) {
                        $entry->save();
                        $flagged++;
                    }

                    $this->sendReminder($entry);
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

        if (! $user instanceof User) {
            return;
        }

        $token = $this->magicLinkService->issueFor($user);

        $user->notify(new OverdueRentReminderNotification($entry, $token));

        $entry->update(['reminder_sent_at' => now()]);
    }

    /**
     * Mark a ledger entry as paid — in full, or a partial amount. Used both
     * when an admin records a payment directly and when an admin approves a
     * renter's self-reported claim. Defaults to settling the full outstanding
     * balance (amount + penalty - amount_paid) when no amount is given.
     */
    public function markPaid(LedgerEntry $entry, User $staff, ?string $notes = null, ?float $amount = null): LedgerEntry
    {
        return DB::transaction(function () use ($entry, $staff, $notes, $amount) {
            // Lock the row for the duration of the read-modify-write cycle so two
            // concurrent payments (e.g. an admin's markPaid racing a renter's
            // submitPayment approval) can't both read the same stale balance.
            $locked = LedgerEntry::where('id', $entry->id)->lockForUpdate()->firstOrFail();

            $settleAmount = $amount ?? (float) $locked->balance;

            $this->applyPayment($locked, $settleAmount, $staff->id, $notes);

            return $locked->fresh();
        });
    }

    /**
     * Apply a payment (full or partial) to a ledger entry: increments
     * amount_paid, flips status to PAID once the full balance (amount +
     * penalty) is settled — issuing an official receipt number — or to
     * PARTIALLY_PAID if a balance remains outstanding.
     */
    protected function applyPayment(LedgerEntry $entry, float $amount, ?int $staffId, ?string $notes = null): void
    {
        $totalDue = (float) $entry->amount + (float) $entry->penalty_amount;
        $newAmountPaid = round(min((float) $entry->amount_paid + $amount, $totalDue), 2);
        $isFullySettled = $newAmountPaid >= $totalDue;

        $update = [
            'amount_paid' => $newAmountPaid,
            'status' => $isFullySettled ? LedgerStatus::PAID : LedgerStatus::PARTIALLY_PAID,
            'notes' => $notes ?? $entry->notes,
        ];

        if ($isFullySettled) {
            $update['paid_at'] = now();
            $update['paid_by'] = $staffId;

            if ($entry->or_number === null) {
                $update['or_number'] = $this->issueOrNumber($entry->tenant_business_id);
            }
        }

        $entry->update($update);
    }

    /**
     * Issue the next sequential official-receipt (OR) number for a tenant
     * business, incrementing its counter atomically to avoid a race between
     * concurrent payments.
     */
    protected function issueOrNumber(int $tenantBusinessId): string
    {
        return DB::transaction(function () use ($tenantBusinessId) {
            $tenantBusiness = TenantBusiness::where('id', $tenantBusinessId)->lockForUpdate()->firstOrFail();
            $prefix = $tenantBusiness->or_prefix ?: 'OR';
            $number = $tenantBusiness->next_or_number;

            $tenantBusiness->update(['next_or_number' => $number + 1]);

            return sprintf('%s-%06d', $prefix, $number);
        });
    }

    /**
     * Renter self-reports that they've paid (in full or in part). Moves the
     * entry into a pending-approval state rather than marking it paid
     * outright — an admin must confirm it (via markPaid) or reject it.
     */
    public function submitPaymentClaim(LedgerEntry $entry, ?string $reference, ?string $notes, ?float $amount = null): LedgerEntry
    {
        return DB::transaction(function () use ($entry, $reference, $notes, $amount) {
            $locked = LedgerEntry::where('id', $entry->id)->lockForUpdate()->firstOrFail();

            abort_unless(
                in_array($locked->status, [LedgerStatus::PENDING, LedgerStatus::OVERDUE, LedgerStatus::PARTIALLY_PAID], true),
                422,
                'This ledger entry cannot be submitted for payment in its current state.'
            );

            $locked->update([
                'status' => LedgerStatus::SUBMITTED,
                'submitted_at' => now(),
                'submitted_amount' => $amount ?? (float) $locked->balance,
                'submission_reference' => $reference,
                'submission_notes' => $notes,
            ]);

            return $locked;
        });
    }

    /**
     * Admin rejects a renter's self-reported payment claim, returning the
     * entry to its unpaid state so the renter can resubmit or pay another way.
     */
    public function rejectPaymentClaim(LedgerEntry $entry, ?string $reason = null): LedgerEntry
    {
        $revertedStatus = match (true) {
            (float) $entry->amount_paid > 0 => LedgerStatus::PARTIALLY_PAID,
            Carbon::parse($entry->due_date)->lt(Carbon::today()) => LedgerStatus::OVERDUE,
            default => LedgerStatus::PENDING,
        };

        $entry->update([
            'status' => $revertedStatus,
            'submitted_at' => null,
            'submitted_amount' => null,
            'submission_reference' => null,
            'submission_notes' => null,
            'notes' => $reason ?? $entry->notes,
        ]);

        return $entry;
    }
}
