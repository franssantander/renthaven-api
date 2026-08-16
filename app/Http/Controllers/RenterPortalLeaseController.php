<?php

namespace App\Http\Controllers;

use App\Services\RenterPortal\RenterPortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RenterPortalLeaseController extends Controller
{
    public function __construct(protected RenterPortalService $renterPortalService) {}

    /**
     * The authenticated renter's current lease, including unit/property.
     */
    public function index(Request $request): JsonResponse
    {
        $renter = $request->user()->renterProfile;

        if (! $renter) {
            return $this->error(null, 'No renter profile found for this account.', Response::HTTP_NOT_FOUND);
        }

        $lease = $this->renterPortalService->getLease($renter);

        return $this->success($lease, 'Lease retrieved successfully.');
    }
}
