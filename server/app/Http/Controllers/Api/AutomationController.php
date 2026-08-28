<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AutomationService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AutomationController extends Controller
{
    use ApiResponse;

    /**
     * Get automation logs history.
     */
    public function logs(Request $request, AutomationService $automationService): JsonResponse
    {
        $businessId = $request->user()->current_business_id;
        $data = $automationService->getLogs($businessId);

        return $this->successResponse($data, 'Automation logs loaded.');
    }

    /**
     * Run manual automation check for low stock and debts.
     */
    public function run(Request $request, AutomationService $automationService): JsonResponse
    {
        $businessId = $request->user()->current_business_id;
        $result = $automationService->runAutomatedChecks($businessId);

        return $this->successResponse($result, "Automated checks completed. {$result['logs_count']} alerts logged.");
    }
}
