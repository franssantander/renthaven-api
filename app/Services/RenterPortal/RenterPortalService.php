<?php

namespace App\Services\RenterPortal;

use App\Data\Lease\LeaseData;
use App\Data\Ledger\LedgerEntryData;
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
        return [
            'lease' => $this->getLease($renter),
            'transactions' => $this->getTransactions($renter, $perPage),
        ];
    }

    public function getLease(Renter $renter): ?LeaseData
    {
        $lease = $renter->activeLease()->with(['propertyUnit.property', 'renter'])->first();

        return $lease ? LeaseData::from($lease) : null;
    }

    public function getTransactions(Renter $renter, int $perPage): PaginatedDataCollection
    {
        $transactions = LedgerEntry::query()
            ->where('renter_id', $renter->id)
            ->with(['lease', 'renter', 'propertyUnit', 'attachments'])
            ->latest('due_date')
            ->paginate($perPage);

        return LedgerEntryData::collect($transactions, PaginatedDataCollection::class);
    }
}
