<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CopilotService;
use App\Services\AI\AIOrchestrator;
use App\Services\AI\AIBriefingService;
use App\Services\AI\AIGoalService;
use App\Services\AI\AITaskService;
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
        $userId = $request->user()->id;
        $response = $copilotService->processQuery($data['prompt'], $businessId, $userId);

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

    /**
     * Confirm or reject a pending high-risk AI action.
     */
    public function confirmAction(Request $request, AIOrchestrator $orchestrator): JsonResponse
    {
        $data = $request->validate([
            'action_id' => ['required', 'integer'],
            'approved'  => ['required', 'boolean'],
        ]);

        $businessId = $request->user()->current_business_id;
        $result = $orchestrator->confirmAction($data['action_id'], $businessId, $data['approved']);

        return $this->successResponse($result, $result['message']);
    }

    /**
     * Get daily morning briefing.
     */
    public function briefing(Request $request, AIBriefingService $briefingService): JsonResponse
    {
        $businessId = $request->user()->current_business_id;
        $briefing = $briefingService->getDailyBriefing($businessId);

        return $this->successResponse($briefing, 'Daily briefing loaded.');
    }

    /**
     * Get active business goals.
     */
    public function goals(Request $request, AIGoalService $goalService): JsonResponse
    {
        $businessId = $request->user()->current_business_id;
        $goals = $goalService->getActiveGoals($businessId);

        return $this->successResponse(['goals' => $goals], 'Goals loaded.');
    }

    /**
     * Set a new business goal.
     */
    public function setGoal(Request $request, AIGoalService $goalService): JsonResponse
    {
        $data = $request->validate([
            'title'        => ['required', 'string', 'max:255'],
            'metric_key'   => ['required', 'string'],
            'target_value' => ['required', 'numeric', 'min:0'],
        ]);

        $businessId = $request->user()->current_business_id;
        $goal = $goalService->setGoal($businessId, $data['title'], $data['metric_key'], (float) $data['target_value']);

        return $this->successResponse($goal, 'Goal set successfully.');
    }

    /**
     * Create a multi-step task.
     */
    public function createTask(Request $request, AITaskService $taskService): JsonResponse
    {
        $data = $request->validate([
            'goal' => ['required', 'string', 'max:500'],
        ]);

        $businessId = $request->user()->current_business_id;
        $userId = $request->user()->id;
        $task = $taskService->createTask($data['goal'], $businessId, $userId);

        return $this->successResponse($task, 'Task created.');
    }
}
