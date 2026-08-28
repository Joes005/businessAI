<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ApiResponse;

    /**
     * Get real-time dashboard analytics for active business.
     */
    public function index(Request $request, DashboardService $dashboardService): JsonResponse
    {
        $businessId = $request->user()->current_business_id;
        $data = $dashboardService->getDashboardData($businessId);

        return $this->successResponse($data, 'Dashboard metrics loaded.');
    }
}
