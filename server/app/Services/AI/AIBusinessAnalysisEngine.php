<?php

namespace App\Services\AI;

use App\Models\Invoice;
use App\Models\Customer;
use Carbon\Carbon;

class AIBusinessAnalysisEngine
{
    protected AICalculationEngine $calcEngine;

    public function __construct(AICalculationEngine $calcEngine)
    {
        $this->calcEngine = $calcEngine;
    }

    /**
     * Deep business analysis outputting Problem + Evidence + Impact + Recommendation.
     */
    public function analyzeBusinessHealth(int $businessId): array
    {
        $growth = $this->calcEngine->calculateSalesGrowth($businessId);
        $paymentRisk = $this->calcEngine->calculatePaymentRisk($businessId);
        $inventoryRisk = $this->calcEngine->calculateInventoryRisk($businessId);

        $analyses = [];

        // 1. Profit & Revenue Trend Analysis
        if ($growth['growth_pct'] < 0) {
            $analyses[] = [
                'type'           => 'REVENUE_DECLINE',
                'problem'        => 'Sales revenue experienced a period-over-period decline.',
                'evidence'       => "This month's revenue is ₹" . number_format($growth['this_month_sales'], 2) . " vs ₹" . number_format($growth['last_month_sales'], 2) . " last month (" . abs($growth['growth_pct']) . "% drop).",
                'impact'         => "Estimated monthly revenue loss of ~₹" . number_format(abs($growth['last_month_sales'] - $growth['this_month_sales']), 2) . ".",
                'recommendation' => "Re-engage top 10 inactive customers and introduce promotional discounts on slow-moving inventory.",
                'action_label'   => 'View Inactive Customers',
            ];
        }

        // 2. Debtor Concentration Analysis
        if ($paymentRisk['total_owed'] > 0) {
            $analyses[] = [
                'type'           => 'DEBTOR_RISK',
                'problem'        => 'High concentration of uncollected customer credit.',
                'evidence'       => "₹" . number_format($paymentRisk['total_owed'], 2) . " is outstanding across {$paymentRisk['debtors_count']} customer(s).",
                'impact'         => "Constrains working capital needed for upcoming stock replenishment.",
                'recommendation' => "Issue automated payment reminders via WhatsApp/SMS before credit terms expire.",
                'action_label'   => 'Draft Payment Reminders',
            ];
        }

        return $analyses;
    }
}
