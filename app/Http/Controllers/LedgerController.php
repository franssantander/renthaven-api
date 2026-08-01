<?php

namespace App\Http\Controllers;

use App\Data\Ledger\LedgerEntryData;
use App\Enum\AuditAction;
use App\Enum\AuditModule;
use App\Enum\LedgerStatus;
use App\Http\Requests\Ledger\MarkPaidRequest;
use App\Http\Requests\Ledger\RejectPaymentRequest;
use App\Http\Requests\Ledger\SubmitPaymentRequest;
use App\Models\LedgerEntry;
use App\Services\AuditLog\AuditLogger;
use App\Services\DashboardMetricService;
use App\Services\Ledger\LedgerService;
use App\Services\PropertyAttachment\PropertyAttachmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelData\PaginatedDataCollection;

class LedgerController extends Controller
{
    public function __construct(
        protected AuditLogger $auditLogger,
        protected LedgerService $ledgerService,
        protected DashboardMetricService $metricService,
        protected PropertyAttachmentService $attachmentService,
    ) {}

    /**
     * Display dashboard metrics for the tenant's rent ledger.
     */
    public function dashboard(Request $request): JsonResponse
    {
        // LedgerEntry is tenant-scoped by its own global scope (and left
        // unscoped for super admins), so no manual filter here.
        $entryQuery = LedgerEntry::query();

        $widgets = [
            $this->metricService->buildSumMetric(
                title: 'Total Collected',
                icon: 'banknotes',
                baseQuery: (clone $entryQuery)->whereIn('status', ['paid', 'partially_paid']),
                column: 'amount_paid',
                dateColumn: 'paid_at',
            ),
            $this->metricService->buildSumMetric(
                title: 'Total Pending',
                icon: 'clock',
                baseQuery: (clone $entryQuery)->where('status', 'pending'),
                column: DB::raw('amount - amount_paid'),
                dateColumn: 'due_date',
            ),
            $this->metricService->buildSumMetric(
                title: 'Total Overdue',
                icon: 'exclamation-triangle',
                baseQuery: (clone $entryQuery)->where('status', 'overdue'),
                column: DB::raw('amount + penalty_amount - amount_paid'),
                dateColumn: 'due_date',
            ),
            $this->metricService->buildCountMetric(
                title: 'Overdue Count',
                icon: 'user-minus',
                baseQuery: (clone $entryQuery)->where('status', 'overdue'),
                dateColumn: 'due_date',
            ),
            $this->metricService->buildCountMetric(
                title: 'Awaiting Approval',
                icon: 'inbox-arrow-down',
                baseQuery: (clone $entryQuery)->where('status', 'submitted'),
                dateColumn: 'submitted_at',
            ),
        ];

        return $this->success([
            'metrics' => $widgets,
        ], 'Dashboard metrics retrieved successfully.');
    }

    /**
     * Display a listing of the tenant business's ledger entries.
     */
    public function index(Request $request)
    {
        $entries = LedgerEntry::query()
            ->with(['lease', 'renter', 'propertyUnit', 'attachments'])
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->input('status'));
            })
            ->when($request->filled('lease_uuid'), function ($query) use ($request) {
                $query->whereHas('lease', function ($leaseQuery) use ($request) {
                    $leaseQuery->where('uuid', $request->input('lease_uuid'));
                });
            })
            ->latest('due_date')
            ->paginate($request->input('per_page', 15));

        return LedgerEntryData::collect($entries, PaginatedDataCollection::class);
    }

    /**
     * Display the authenticated renter's own ledger entries.
     */
    public function mine(Request $request)
    {
        $renter = $request->user()->renterProfile;

        if (! $renter) {
            return $this->success([], 'No ledger entries found.');
        }

        $entries = LedgerEntry::query()
            ->with(['lease', 'renter', 'propertyUnit', 'attachments'])
            ->where('renter_id', $renter->id)
            ->latest('due_date')
            ->paginate($request->input('per_page', 15));

        return LedgerEntryData::collect($entries, PaginatedDataCollection::class);
    }

    /**
     * Mark the specified ledger entry as paid.
     */
    public function markPaid(MarkPaidRequest $request, LedgerEntry $ledgerEntry): JsonResponse
    {
        if ($ledgerEntry->status === LedgerStatus::PAID) {
            return $this->error(null, 'This ledger entry has already been marked as paid.', 422);
        }

        $originalValues = $ledgerEntry->getOriginal();

        // An explicit `amount` always wins; otherwise, approving a renter's
        // submitted claim settles exactly what they claimed to have paid.
        $amount = $request->validated('amount') !== null
            ? (float) $request->validated('amount')
            : ($ledgerEntry->status === LedgerStatus::SUBMITTED && $ledgerEntry->submitted_amount !== null
                ? (float) $ledgerEntry->submitted_amount
                : null);

        $this->ledgerService->markPaid($ledgerEntry, $request->user(), $request->validated('notes'), $amount);

        $this->auditLogger->record(
            module: AuditModule::BILLING,
            action: AuditAction::UPDATED,
            description: "Marked ledger entry ID {$ledgerEntry->id} as paid",
            auditable: $ledgerEntry,
            oldValues: $originalValues,
        );

        return $this->success(LedgerEntryData::from($ledgerEntry->fresh()), 'Ledger entry marked as paid.');
    }

    /**
     * Renter self-reports payment for one of their own ledger entries,
     * putting it into a pending-approval state for an admin to confirm.
     */
    public function submitPayment(SubmitPaymentRequest $request, LedgerEntry $ledgerEntry): JsonResponse
    {
        $renter = $request->user()->renterProfile;

        abort_unless($renter && $ledgerEntry->renter_id === $renter->id, 404);

        $originalValues = $ledgerEntry->getOriginal();

        // Atomic: the payment claim and its proof-of-payment attachment must
        // both land, or neither should — otherwise a submitted claim can end
        // up with no proof on file (or vice versa).
        DB::transaction(function () use ($request, $ledgerEntry) {
            $this->ledgerService->submitPaymentClaim(
                $ledgerEntry,
                $request->validated('reference'),
                $request->validated('notes'),
                $request->validated('amount') !== null ? (float) $request->validated('amount') : null,
            );

            $this->attachmentService->attach($ledgerEntry, [$request->file('proof')], ['Proof of payment']);
        });

        $this->auditLogger->record(
            module: AuditModule::BILLING,
            action: AuditAction::UPDATED,
            description: "Renter submitted a payment claim for ledger entry ID {$ledgerEntry->id}",
            auditable: $ledgerEntry,
            oldValues: $originalValues,
        );

        return $this->success(LedgerEntryData::from($ledgerEntry->fresh()->load('attachments')), 'Payment submitted. An admin will review and confirm it shortly.');
    }

    /**
     * Reject a renter's self-reported payment claim, returning the entry to
     * its unpaid state.
     */
    public function rejectPayment(RejectPaymentRequest $request, LedgerEntry $ledgerEntry): JsonResponse
    {
        if ($ledgerEntry->status !== LedgerStatus::SUBMITTED) {
            return $this->error(null, 'This ledger entry has no pending payment claim to reject.', 422);
        }

        $originalValues = $ledgerEntry->getOriginal();

        $this->ledgerService->rejectPaymentClaim($ledgerEntry, $request->validated('reason'));

        $this->auditLogger->record(
            module: AuditModule::BILLING,
            action: AuditAction::UPDATED,
            description: "Rejected payment claim for ledger entry ID {$ledgerEntry->id}",
            auditable: $ledgerEntry,
            oldValues: $originalValues,
        );

        return $this->success(LedgerEntryData::from($ledgerEntry->fresh()), 'Payment claim rejected.');
    }
}
