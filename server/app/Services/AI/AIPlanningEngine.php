<?php

namespace App\Services\AI;

use Illuminate\Support\Str;

class AIPlanningEngine
{
    /**
     * Create a structured multi-step execution plan for a goal.
     */
    public function generatePlan(string $goal, int $businessId): array
    {
        $normalized = Str::lower($goal);
        $steps = [];

        if (Str::contains($normalized, ['reminder', 'overdue', 'collect debt', 'debtors', 'kadan'])) {
            $steps = [
                [
                    'step_number' => 1,
                    'tool_name'   => 'get_pending_payments',
                    'arguments'   => [],
                    'risk_level'  => 'SAFE_READ',
                    'description' => 'Retrieve list of overdue customer balances.',
                ],
                [
                    'step_number' => 2,
                    'tool_name'   => 'prepare_payment_reminder',
                    'arguments'   => ['customer_name' => 'Overdue Customers'],
                    'risk_level'  => 'HIGH_RISK_WRITE',
                    'description' => 'Prepare automated payment reminders for confirmation.',
                ],
            ];
        } elseif (Str::contains($normalized, ['restock', 'low stock', 'inventory risk'])) {
            $steps = [
                [
                    'step_number' => 1,
                    'tool_name'   => 'get_low_stock_products',
                    'arguments'   => [],
                    'risk_level'  => 'SAFE_READ',
                    'description' => 'Calculate inventory stockout risk based on sales velocity.',
                ],
                [
                    'step_number' => 2,
                    'tool_name'   => 'calculate_inventory_risk',
                    'arguments'   => [],
                    'risk_level'  => 'SAFE_READ',
                    'description' => 'Estimate days remaining before stockout.',
                ],
            ];
        } else {
            $steps = [
                [
                    'step_number' => 1,
                    'tool_name'   => 'get_today_sales',
                    'arguments'   => [],
                    'risk_level'  => 'SAFE_READ',
                    'description' => 'Fetch current sales and bill metrics.',
                ],
                [
                    'step_number' => 2,
                    'tool_name'   => 'calculate_profit',
                    'arguments'   => ['period' => 'this_month'],
                    'risk_level'  => 'SAFE_READ',
                    'description' => 'Calculate net gross profit and margin.',
                ],
            ];
        }

        return [
            'goal'          => $goal,
            'steps_count'   => count($steps),
            'steps'         => $steps,
            'requires_confirmation' => collect($steps)->contains('risk_level', 'HIGH_RISK_WRITE'),
        ];
    }
}
