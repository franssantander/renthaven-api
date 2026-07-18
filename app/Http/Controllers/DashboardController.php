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
}
