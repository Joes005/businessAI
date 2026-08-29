<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AI\AIEvaluationService;

class EvaluateAiEngine extends Command
{
    protected $signature = 'ai:evaluate';
    protected $description = 'Run automated benchmarks and evaluations for the AI Business Copilot Intelligence Engine';

    public function handle(AIEvaluationService $evalService): int
    {
        $this->info('Starting AI Business Engine Benchmark Evaluation...');

        $res = $evalService->runEvaluations();

        $this->table(
            ['Prompt', 'Category', 'Expected Intent', 'Actual Intent', 'Passed'],
            array_map(fn($r) => [
                $r['prompt'],
                $r['category'],
                $r['expected_intent'],
                $r['actual_intent'],
                $r['passed'] ? '✅ PASS' : '❌ FAIL',
            ], $res['results'])
        );

        $this->info("Evaluation Completed: {$res['passed']}/{$res['total']} passed ({$res['pass_rate']})");

        return $res['failed'] === 0 ? 0 : 1;
    }
}
