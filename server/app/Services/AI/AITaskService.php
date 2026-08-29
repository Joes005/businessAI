<?php

namespace App\Services\AI;

use App\Models\AiTask;
use App\Models\AiTaskStep;
use Carbon\Carbon;

class AITaskService
{
    protected AIPlanningEngine $planningEngine;
    protected AIActionExecutor $actionExecutor;
    protected AIActionVerificationService $verificationService;

    public function __construct(
        AIPlanningEngine $planningEngine,
        AIActionExecutor $actionExecutor,
        AIActionVerificationService $verificationService
    ) {
        $this->planningEngine     = $planningEngine;
        $this->actionExecutor     = $actionExecutor;
        $this->verificationService = $verificationService;
    }

    /**
     * Create and initiate a new AI multi-step task.
     */
    public function createTask(string $goal, int $businessId, int $userId): AiTask
    {
        $planData = $this->planningEngine->generatePlan($goal, $businessId);

        $task = AiTask::create([
            'business_id' => $businessId,
            'user_id'     => $userId,
            'goal'        => $goal,
            'status'      => $planData['requires_confirmation'] ? 'WAITING_FOR_CONFIRMATION' : 'PLANNING',
            'plan'        => $planData,
        ]);

        foreach ($planData['steps'] as $s) {
            AiTaskStep::create([
                'ai_task_id'  => $task->id,
                'step_number' => $s['step_number'],
                'tool_name'   => $s['tool_name'],
                'arguments'   => $s['arguments'],
                'risk_level'  => $s['risk_level'],
                'status'      => 'PENDING',
            ]);
        }

        if (!$planData['requires_confirmation']) {
            $this->executeTask($task->id, $businessId);
        }

        return $task->fresh(['steps']);
    }

    /**
     * Execute steps in a task sequentially.
     */
    public function executeTask(int $taskId, int $businessId): array
    {
        $task = AiTask::where('business_id', $businessId)->with('steps')->find($taskId);
        if (!$task) {
            return ['success' => false, 'message' => 'Task not found.'];
        }

        $task->update(['status' => 'EXECUTING']);

        foreach ($task->steps as $step) {
            if ($step->status === 'COMPLETED') continue;

            $step->update(['status' => 'EXECUTING']);
            $execResult = $this->actionExecutor->execute($step->tool_name, $step->arguments ?? [], $businessId, $task->user_id);

            $step->update([
                'status' => 'VERIFYING',
                'result' => json_encode($execResult),
            ]);

            $verified = $this->verificationService->verifyStep($step, $businessId);

            if ($verified) {
                $step->update(['status' => 'COMPLETED']);
            } else {
                $step->update(['status' => 'FAILED', 'error' => 'Verification failed.']);
                $task->update(['status' => 'FAILED']);
                return ['success' => false, 'message' => "Task failed at step {$step->step_number}."];
            }
        }

        $task->update([
            'status'       => 'COMPLETED',
            'completed_at' => Carbon::now(),
        ]);

        return [
            'success' => true,
            'message' => 'Task completed and verified successfully.',
            'task'    => $task->fresh(['steps']),
        ];
    }
}
