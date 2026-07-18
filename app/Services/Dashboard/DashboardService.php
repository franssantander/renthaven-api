<?php

namespace App\Services\Dashboard;

use App\Data\Ledger\LedgerEntryData;
use App\Enum\LedgerStatus;
use App\Enum\Role;
use App\Models\LedgerEntry;
use App\Models\Property;
use App\Models\Renter;
use App\Models\User;
use App\Services\DashboardMetricService;
use Illuminate\Database\Eloquent\Builder;
use Spatie\LaravelData\PaginatedDataCollection;

class DashboardService
{
    public function __construct(protected DashboardMetricService $metricService) {}

    /**
     * Build the admin/super-admin overview widgets: total properties, active
     * tenants, payments awaiting approval, and this month's collected revenue.
     * Super admins see figures across every tenant business; everyone else is
     * scoped to their own.
     */
    public function getMetrics(User $user): array
    {
        $isSuperAdmin = $this->isSuperAdmin($user);

        // Property is auto-scoped to the tenant via the BelongsToTenantBusiness
        // trait (and left unscoped for super admins), so no manual filter here.
        $propertyQuery = Property::query();

        $renterQuery = Renter::query()
            ->whereHas('activeLease')
            ->when(!$isSuperAdmin, fn ($query) => $query->where('tenant_business_id', $user->tenant_business_id));

        $ledgerQuery = $this->scopedLedgerQuery($user);

        return [
            $this->metricService->buildCountMetric(
                title: 'Total Properties',
                icon: 'building-office',
                baseQuery: $propertyQuery,
            ),
            $this->metricService->buildCountMetric(
                title: 'Active Tenants',
                icon: 'users',
                baseQuery: $renterQuery,
            ),
            $this->metricService->buildCountMetric(
                title: 'Pending Approvals',
                icon: 'clock',
                baseQuery: (clone $ledgerQuery)->where('status', LedgerStatus::SUBMITTED),
                dateColumn: 'submitted_at',
            ),
            $this->metricService->buildMonthlySumMetric(
                title: 'Monthly Revenue',
                icon: 'banknotes',
                baseQuery: (clone $ledgerQuery)->where('status', LedgerStatus::PAID),
                column: 'amount',
                dateColumn: 'paid_at',
            ),
        ];
    }

    /**
     * Renter-submitted payment claims awaiting admin approval, with the
     * lease, unit, and property details needed to review each one.
     */
    public function getPendingApprovals(User $user, int $perPage): PaginatedDataCollection
    {
        $entries = $this->scopedLedgerQuery($user)
            ->where('status', LedgerStatus::SUBMITTED)
            ->with(['lease', 'renter', 'propertyUnit.property'])
            ->latest('submitted_at')
            ->paginate($perPage);

        return LedgerEntryData::collect($entries, PaginatedDataCollection::class);
    }

    /**
     * Recent ledger activity across every status (paid, pending, overdue,
     * submitted), most recently updated first.
     */
    public function getRecentActivity(User $user, int $perPage): PaginatedDataCollection
    {
        $entries = $this->scopedLedgerQuery($user)
            ->with(['lease', 'renter', 'propertyUnit.property'])
            ->latest('updated_at')
            ->paginate($perPage);

        return LedgerEntryData::collect($entries, PaginatedDataCollection::class);
    }

    protected function isSuperAdmin(User $user): bool
    {
        return $user->role?->slug === Role::SUPER_ADMIN->value;
    }

    protected function scopedLedgerQuery(User $user): Builder
    {
        return LedgerEntry::query()
            ->when(!$this->isSuperAdmin($user), fn ($query) => $query->where('tenant_business_id', $user->tenant_business_id));
    }
}
