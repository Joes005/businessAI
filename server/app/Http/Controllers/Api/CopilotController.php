<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CopilotService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CopilotController extends Controller
{
    use ApiResponse;

    /**
     * Process conversational AI Copilot chat query.
     */
    public function chat(Request $request, CopilotService $copilotService): JsonResponse
    {
        $data = $request->validate([
            'prompt' => ['required', 'string', 'max:500'],
        ]);

        $businessId = $request->user()->current_business_id;
        $response = $copilotService->processQuery($data['prompt'], $businessId);

        return $this->successResponse($response, 'Copilot response generated.');
    }

    /**
     * Get proactive AI business recommendations.
     */
    public function insights(Request $request, CopilotService $copilotService): JsonResponse
    {
        $businessId = $request->user()->current_business_id;
        $insights = $copilotService->getProactiveInsights($businessId);

        return $this->successResponse([
            'insights' => $insights,
        ], 'Copilot insights loaded.');
    }
}
