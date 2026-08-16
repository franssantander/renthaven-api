<?php

namespace App\Http\Controllers;

use App\Services\RenterPortal\RenterPortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RenterPortalDashboardController extends Controller
{
    public function __construct(protected RenterPortalService $renterPortalService) {}

    /**
     * The authenticated renter's self-serve dashboard: current lease/unit/property
     * and payment transaction history.
     */
    public function index(Request $request): JsonResponse
    {
        $renter = $request->user()->renterProfile;

        if (! $renter) {
            return $this->error(null, 'No renter profile found for this account.', Response::HTTP_NOT_FOUND);
        }

        $dashboard = $this->renterPortalService->getDashboard($renter, (int) $request->input('per_page', 15));

        return $this->success($dashboard, 'Dashboard retrieved successfully.');
    }
}
