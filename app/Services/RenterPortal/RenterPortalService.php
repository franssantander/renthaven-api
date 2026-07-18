<?php

namespace App\Services\RenterPortal;

use App\Data\Ledger\LedgerEntryData;
use App\Data\Lease\LeaseData;
use App\Models\LedgerEntry;
use App\Models\Renter;
use Spatie\LaravelData\PaginatedDataCollection;

class RenterPortalService
{
    /**
     * Build the renter's self-serve dashboard: their current lease/unit/property
     * and a paginated payment transaction history.
     */
    public function getDashboard(Renter $renter, int $perPage): array
    {
        $lease = $renter->activeLease()->with('propertyUnit.property')->first();

        $transactions = LedgerEntry::query()
            ->where('renter_id', $renter->id)
            ->with(['lease', 'renter', 'propertyUnit', 'attachments'])
            ->latest('due_date')
            ->paginate($perPage);

        return [
            'lease'        => $lease ? LeaseData::from($lease) : null,
            'transactions' => LedgerEntryData::collect($transactions, PaginatedDataCollection::class),
        ];
    }
}
