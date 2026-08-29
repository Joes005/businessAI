<?php

namespace App\Services\AI;

use App\Models\AiEvaluation;
use App\Models\Business;
use App\Models\User;
use Carbon\Carbon;

class AIEvaluationService
{
    protected AIOrchestrator $orchestrator;

    public function __construct(AIOrchestrator $orchestrator)
    {
        $this->orchestrator = $orchestrator;
    }

    /**
     * Run test benchmark evaluations and calculate pass rate.
     */
    public function runEvaluations(?int $businessId = null): array
    {
        if (!$businessId) {
            $user = User::firstOrCreate(
                ['email' => 'eval_admin@copilot.com'],
                ['name' => 'Eval Admin', 'password' => bcrypt('password123')]
            );

            $business = Business::firstOrCreate(
                ['name' => 'Benchmark Superstore'],
                ['type' => 'retail', 'currency' => 'INR']
            );

            $businessId = $business->id;
            $userId = $user->id;
        } else {
            $userId = 1;
        }

        $evaluations = AiEvaluation::all();
        $passedCount = 0;
        $totalCount = $evaluations->count();
        $results = [];

        foreach ($evaluations as $eval) {
            $response = $this->orchestrator->handleUserRequest($eval->user_prompt, $businessId, $userId);

            $intentMatched = empty($eval->expected_intent) || ($response['intent'] === $eval->expected_intent);
            $passed = $intentMatched;

            $eval->update([
                'passed'            => $passed,
                'actual_response'   => $response['answer'],
                'last_evaluated_at' => Carbon::now(),
            ]);

            if ($passed) {
                $passedCount++;
            }

            $results[] = [
                'prompt'          => $eval->user_prompt,
                'category'        => $eval->category,
                'expected_intent' => $eval->expected_intent,
                'actual_intent'   => $response['intent'],
                'passed'          => $passed,
            ];
        }

        $passRate = $totalCount > 0 ? round(($passedCount / $totalCount) * 100, 1) : 0.0;

        return [
            'total'     => $totalCount,
            'passed'    => $passedCount,
            'failed'    => $totalCount - $passedCount,
            'pass_rate' => "{$passRate}%",
            'results'   => $results,
        ];
    }
}
