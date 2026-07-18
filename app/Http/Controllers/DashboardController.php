<?php

namespace App\Http\Controllers;

use App\Services\Dashboard\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(protected DashboardService $dashboardService) {}

    /**
     * Overview metrics for the admin/super-admin dashboard.
     */
    public function index(Request $request): JsonResponse
    {
        $metrics = $this->dashboardService->getMetrics($request->user());

        return $this->success(['metrics' => $metrics], 'Dashboard metrics retrieved successfully.');
    }

    /**
     * Renter-submitted payment claims awaiting approval, with lease, amount,
     * property, and unit details for each.
     */
    public function pendingApprovals(Request $request)
    {
        return $this->dashboardService->getPendingApprovals($request->user(), (int) $request->input('per_page', 15));
    }

    /**
     * Recent ledger activity across every payment status.
     */
    public function recentActivity(Request $request)
    {
        return $this->dashboardService->getRecentActivity($request->user(), (int) $request->input('per_page', 15));
    }
}
