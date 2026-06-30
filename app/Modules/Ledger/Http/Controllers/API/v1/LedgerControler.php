<?php

namespace App\Modules\Ledger\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Modules\Ledger\DTO\LeasePaymentData;
use App\Modules\Ledger\Models\LeasePayment;
use Illuminate\Http\Request;
use Spatie\LaravelData\PaginatedDataCollection;

class LedgerControler extends Controller
{
    public function index(Request $request)
    {
        $query = LeasePayment::with(['lease.property', 'lease.renter'])
            ->whereHas('lease', fn($q) => $q->where('is_active', true));

        if ($request->filled('status')) {
            $status = $request->status;

            $query->when($status === 'Paid', fn($q) => $q->where('status', 'paid'))
                ->when($status === 'Overdue', fn($q) => $q->where('status', '!=', 'paid')
                    ->where('due_date', '<', now()))
                ->when($status === 'Pending', fn($q) => $q->where('status', '!=', 'paid')
                    ->where('due_date', '>=', now()));
        }

        $payments = $query->orderBy('due_date', 'desc')->paginate(15);

        return LeasePaymentData::collect($payments, PaginatedDataCollection::class);
    }

    public function store(Request $request)
    {
        //
    }

    public function show($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function destroy($id)
    {
        //
    }
}