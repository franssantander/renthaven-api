<?php

namespace App\Http\Controllers;

use App\Services\RenterPortal\RenterPortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RenterPortalTransactionController extends Controller
{
    public function __construct(protected RenterPortalService $renterPortalService) {}

    /**
     * The authenticated renter's paginated payment transaction history.
     */
    public function index(Request $request): JsonResponse
    {
        $renter = $request->user()->renterProfile;

        if (! $renter) {
            return $this->error(null, 'No renter profile found for this account.', Response::HTTP_NOT_FOUND);
        }

        $transactions = $this->renterPortalService->getTransactions($renter, (int) $request->input('per_page', 15));

        return $this->success($transactions, 'Transactions retrieved successfully.');
    }
}
