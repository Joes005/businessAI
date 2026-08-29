<?php

namespace App\Services\AI;

use App\Models\AiInsight;
use App\Models\Product;
use App\Models\Invoice;
use App\Models\Customer;
use Carbon\Carbon;

class AIInsightEngine
{
    protected AICalculationEngine $calcEngine;

    public function __construct(AICalculationEngine $calcEngine)
    {
        $this->calcEngine = $calcEngine;
    }

    /**
     * Generate proactive insights for a business and persist to database.
     */
    public function generateInsights(int $businessId): array
    {
        $insights = [];

        // 1. Stockout & Low Stock Risk
        $riskData = $this->calcEngine->calculateInventoryRisk($businessId);
        if ($riskData['low_stock_count'] > 0) {
            $topRisk = $riskData['items'][0];
            $insights[] = [
                'type'           => 'STOCK_WARNING',
                'title'          => "Low Stock Alert: {$topRisk['name']}",
                'severity'       => 'high',
                'problem'        => "Product '{$topRisk['name']}' has only {$topRisk['current_stock']} unit(s) remaining.",
                'impact'         => "Based on recent sales velocity ({$topRisk['daily_velocity']} units/day), stock may run out in ~{$topRisk['estimated_days_remaining']} day(s).",
                'recommendation' => "Consider restocking at least 20 units immediately to avoid lost sales revenue.",
                'supporting_data'=> $topRisk,
            ];
        }

        // 2. Pending Debtor Risk
        $paymentRisk = $this->calcEngine->calculatePaymentRisk($businessId);
        if ($paymentRisk['total_owed'] > 0) {
            $insights[] = [
                'type'           => 'DEBT_WARNING',
                'title'          => "Uncollected Customer Receivables",
                'severity'       => $paymentRisk['risk_level'] === 'HIGH' ? 'high' : 'medium',
                'problem'        => "You have ₹" . number_format($paymentRisk['total_owed'], 2) . " in outstanding balances across {$paymentRisk['debtors_count']} customer(s).",
                'impact'         => "Delayed payments compress cash flow and increase default risk.",
                'recommendation' => "Send payment reminders to overdue customers using WhatsApp/SMS.",
                'supporting_data'=> $paymentRisk,
            ];
        }

        // 3. Period Sales Growth / Drop Detection
        $growth = $this->calcEngine->calculateSalesGrowth($businessId);
        if ($growth['growth_pct'] < -10) {
            $insights[] = [
                'type'           => 'SALES_DROP',
                'title'          => "Sales Decline Detected",
                'severity'       => 'medium',
                'problem'        => "Monthly sales are down by " . abs($growth['growth_pct']) . "% compared to last month.",
                'impact'         => "Total revenue this month is ₹" . number_format($growth['this_month_sales'], 2) . " vs ₹" . number_format($growth['last_month_sales'], 2) . " last month.",
                'recommendation' => "Promote best-selling products or offer weekend discounts to re-engage past customers.",
                'supporting_data'=> $growth,
            ];
        }

        // Sync insights with database
        foreach ($insights as $insightData) {
            AiInsight::updateOrCreate(
                [
                    'business_id' => $businessId,
                    'title'       => $insightData['title'],
                ],
                array_merge($insightData, ['status' => 'active'])
            );
        }

        return AiInsight::where('business_id', $businessId)
            ->where('status', 'active')
            ->orderByDesc('created_at')
            ->get()
            ->toArray();
    }
}
