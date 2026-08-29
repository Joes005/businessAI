<?php

namespace App\Services\AI;

use App\Models\AiBusinessGoal;
use App\Models\Invoice;
use Carbon\Carbon;

class AIGoalService
{
    /**
     * Set a new business goal.
     */
    public function setGoal(int $businessId, string $title, string $metricKey, float $targetValue): AiBusinessGoal
    {
        $baseline = (float) Invoice::where('business_id', $businessId)
            ->where('date', '>=', Carbon::now()->startOfMonth())
            ->sum('grand_total');

        return AiBusinessGoal::create([
            'business_id'    => $businessId,
            'title'          => $title,
            'metric_key'     => $metricKey,
            'baseline_value' => $baseline,
            'target_value'   => $targetValue,
            'current_value'  => $baseline,
            'status'         => 'ACTIVE',
        ]);
    }

    /**
     * Get active business goals with progress % calculation.
     */
    public function getActiveGoals(int $businessId): array
    {
        $goals = AiBusinessGoal::where('business_id', $businessId)
            ->where('status', 'ACTIVE')
            ->get();

        return $goals->map(function ($g) {
            $range = max(1, $g->target_value - $g->baseline_value);
            $currentDiff = max(0, $g->current_value - $g->baseline_value);
            $progressPct = min(100, round(($currentDiff / $range) * 100, 1));

            return [
                'id'             => $g->id,
                'title'          => $g->title,
                'baseline_value' => $g->baseline_value,
                'target_value'   => $g->target_value,
                'current_value'  => $g->current_value,
                'progress_pct'   => $progressPct,
                'status'         => $g->status,
            ];
        })->toArray();
    }
}
