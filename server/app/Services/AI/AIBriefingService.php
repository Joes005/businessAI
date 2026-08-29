<?php

namespace App\Services\AI;

use App\Models\Invoice;
use App\Models\Customer;
use App\Models\Product;
use Carbon\Carbon;

class AIBriefingService
{
    protected AICalculationEngine $calcEngine;

    public function __construct(AICalculationEngine $calcEngine)
    {
        $this->calcEngine = $calcEngine;
    }

    /**
     * Generate structured morning briefing for the business owner.
     */
    public function getDailyBriefing(int $businessId): array
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        $todaySales = (float) Invoice::where('business_id', $businessId)->whereDate('date', $today)->sum('grand_total');
        $yesterdaySales = (float) Invoice::where('business_id', $businessId)->whereDate('date', $yesterday)->sum('grand_total');

        $salesDiffPct = 0.0;
        if ($yesterdaySales > 0) {
            $salesDiffPct = round((($todaySales - $yesterdaySales) / $yesterdaySales) * 100, 1);
        }

        $paymentRisk = $this->calcEngine->calculatePaymentRisk($businessId);
        $inventoryRisk = $this->calcEngine->calculateInventoryRisk($businessId);

        $priorities = [];
        if ($inventoryRisk['low_stock_count'] > 0) {
            $priorities[] = "Restock {$inventoryRisk['low_stock_count']} low-stock product(s).";
        }
        if ($paymentRisk['total_owed'] > 0) {
            $priorities[] = "Follow up with {$paymentRisk['debtors_count']} customer(s) owing debt.";
        }

        if (empty($priorities)) {
            $priorities[] = "All operations normal. Focus on promoting top products.";
        }

        return [
            'date'               => Carbon::now()->format('F j, Y'),
            'today_sales'        => $todaySales,
            'yesterday_sales'    => $yesterdaySales,
            'sales_change_pct'   => $salesDiffPct,
            'uncollected_debt'   => $paymentRisk['total_owed'],
            'low_stock_count'    => $inventoryRisk['low_stock_count'],
            'top_priorities'     => $priorities,
            'briefing_text'      => "Good morning 👋 Here is your daily business briefing:\nToday's Sales: ₹" . number_format($todaySales, 2) . " (" . ($salesDiffPct >= 0 ? "+{$salesDiffPct}%" : "{$salesDiffPct}%") . " vs yesterday).\nUncollected Debt: ₹" . number_format($paymentRisk['total_owed'], 2) . " across {$paymentRisk['debtors_count']} debtors.\nItems Needing Attention: {$inventoryRisk['low_stock_count']} low stock products.",
        ];
    }
}
