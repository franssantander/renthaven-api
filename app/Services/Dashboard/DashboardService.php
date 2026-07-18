<?php

namespace App\Services\Dashboard;

use App\Enum\LedgerStatus;
use App\Enum\Role;
use App\Models\LedgerEntry;
use App\Models\Property;
use App\Models\Renter;
use App\Models\User;
use App\Services\DashboardMetricService;

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
        $isSuperAdmin = $user->role?->slug === Role::SUPER_ADMIN->value;
        $tenantBusinessId = $user->tenant_business_id;

        // Property is auto-scoped to the tenant via the BelongsToTenantBusiness
        // trait (and left unscoped for super admins), so no manual filter here.
        $propertyQuery = Property::query();

        $renterQuery = Renter::query()
            ->whereHas('activeLease')
            ->when(!$isSuperAdmin, fn ($query) => $query->where('tenant_business_id', $tenantBusinessId));

        $ledgerQuery = LedgerEntry::query()
            ->when(!$isSuperAdmin, fn ($query) => $query->where('tenant_business_id', $tenantBusinessId));

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
}
