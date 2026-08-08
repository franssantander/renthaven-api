<?php

namespace App\Http\Controllers;

use App\Data\Renter\RenterData;
use App\Models\Renter;
use Illuminate\Http\Request;
use Spatie\LaravelData\PaginatedDataCollection;

class RenterController extends Controller
{
    /**
     * Display a listing of renters belonging to the caller's tenant
     * business who don't currently have an active lease, optionally
     * filtered by a name/email search term. Used to pick an available
     * existing tenant when assigning them to a property unit.
     */
    public function index(Request $request)
    {
        $tenantBusinessId = $request->user()->tenant_business_id;
        $search = $request->input('search');

        $renters = Renter::query()
            ->with('user')
            ->where('tenant_business_id', $tenantBusinessId)
            ->whereDoesntHave('activeLease')
            ->when($search, fn($query) => $query->where(fn($inner) => $inner
                ->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->orderBy('first_name')
            ->paginate($request->input('per_page', 15));

        return RenterData::collect($renters, PaginatedDataCollection::class);
    }
}
