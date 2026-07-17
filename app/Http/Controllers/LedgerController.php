<?php

namespace App\Http\Controllers;

use App\Data\Ledger\LedgerEntryData;
use App\Enum\AuditAction;
use App\Enum\AuditModule;
use App\Enum\LedgerStatus;
use App\Http\Requests\Ledger\MarkPaidRequest;
use App\Models\LedgerEntry;
use App\Services\AuditLog\AuditLogger;
use App\Services\DashboardMetricService;
use App\Services\Ledger\LedgerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\LaravelData\PaginatedDataCollection;

class LedgerController extends Controller
{
    public function __construct(
        protected AuditLogger $auditLogger,
        protected LedgerService $ledgerService,
        protected DashboardMetricService $metricService,
    ) {}

    /**
     * Display dashboard metrics for the tenant's rent ledger.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $tenantBusinessId = $request->user()->tenant_business_id;

        $entryQuery = LedgerEntry::query()->where('tenant_business_id', $tenantBusinessId);

        $widgets = [
            $this->metricService->buildSumMetric(
                title: 'Total Collected',
                icon: 'banknotes',
                baseQuery: (clone $entryQuery)->where('status', 'paid'),
                column: 'amount',
                dateColumn: 'paid_at',
            ),
            $this->metricService->buildSumMetric(
                title: 'Total Pending',
                icon: 'clock',
                baseQuery: (clone $entryQuery)->where('status', 'pending'),
                column: 'amount',
                dateColumn: 'due_date',
            ),
            $this->metricService->buildSumMetric(
                title: 'Total Overdue',
                icon: 'exclamation-triangle',
                baseQuery: (clone $entryQuery)->where('status', 'overdue'),
                column: 'amount',
                dateColumn: 'due_date',
            ),
            $this->metricService->buildCountMetric(
                title: 'Overdue Count',
                icon: 'user-minus',
                baseQuery: (clone $entryQuery)->where('status', 'overdue'),
                dateColumn: 'due_date',
            ),
        ];

        return $this->success([
            'metrics' => $widgets
        ], 'Dashboard metrics retrieved successfully.');
    }

    /**
     * Display a listing of the tenant business's ledger entries.
     */
    public function index(Request $request)
    {
        $tenantBusinessId = $request->user()->tenant_business_id;

        $entries = LedgerEntry::query()
            ->with(['lease', 'renter', 'propertyUnit'])
            ->where('tenant_business_id', $tenantBusinessId)
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

        if (!$renter) {
            return $this->success([], 'No ledger entries found.');
        }

        $entries = LedgerEntry::query()
            ->with(['lease', 'renter', 'propertyUnit'])
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
        abort_unless($ledgerEntry->tenant_business_id === $request->user()->tenant_business_id, 404);

        if ($ledgerEntry->status === LedgerStatus::PAID) {
            return $this->error(null, 'This ledger entry has already been marked as paid.', 422);
        }

        $originalValues = $ledgerEntry->getOriginal();

        $this->ledgerService->markPaid($ledgerEntry, $request->user(), $request->validated('notes'));

        $this->auditLogger->record(
            module: AuditModule::BILLING,
            action: AuditAction::UPDATED,
            description: "Marked ledger entry ID {$ledgerEntry->id} as paid",
            auditable: $ledgerEntry,
            oldValues: $originalValues,
        );

        return $this->success($ledgerEntry, 'Ledger entry marked as paid.');
    }
}
