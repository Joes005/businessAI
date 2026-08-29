<?php

namespace App\Services;

use App\Services\AI\AIOrchestrator;
use App\Services\AI\AIInsightEngine;
use App\Models\Business;

class CopilotService
{
    protected AIOrchestrator $orchestrator;
    protected AIInsightEngine $insightEngine;

    public function __construct(AIOrchestrator $orchestrator, AIInsightEngine $insightEngine)
    {
        $this->orchestrator  = $orchestrator;
        $this->insightEngine = $insightEngine;
    }

    /**
     * Interpret natural language query, execute AI Orchestrator tool chain, and return structured AI response.
     */
    public function processQuery(string $prompt, int $businessId, ?int $userId = null): array
    {
        return $this->orchestrator->handleUserRequest($prompt, $businessId, $userId);
    }

    /**
     * Get proactive business health alerts and suggestions from AI Insight Engine.
     */
    public function getProactiveInsights(int $businessId): array
    {
        $dbInsights = $this->insightEngine->generateInsights($businessId);

        return array_map(function ($item) {
            return [
                'type'        => $item['type'],
                'title'       => $item['title'],
                'severity'    => $item['severity'],
                'problem'     => $item['problem'],
                'impact'      => $item['impact'],
                'message'     => $item['problem'] . " " . $item['impact'],
                'action_label'=> $item['recommendation'],
                'route'       => $item['type'] === 'STOCK_WARNING' ? '/products' : ($item['type'] === 'DEBT_WARNING' ? '/customers' : '/reports'),
            ];
        }, $dbInsights);
    }
}
