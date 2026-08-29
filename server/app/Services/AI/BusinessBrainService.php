<?php

namespace App\Services\AI;

use App\Models\Business;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Customer;
use Carbon\Carbon;

class BusinessBrainService
{
    protected AICalculationEngine $calcEngine;

    public function __construct(AICalculationEngine $calcEngine)
    {
        $this->calcEngine = $calcEngine;
    }

    /**
     * Build structured Business Brain context model.
     */
    public function buildBrainModel(int $businessId): array
    {
        $business = Business::find($businessId);
        if (!$business) {
            return [];
        }

        $currency = $business->currency === 'USD' ? '$' : ($business->currency === 'EUR' ? '€' : '₹');

        // Financial & Sales Health
        $profitThisMonth = $this->calcEngine->calculateProfit($businessId, 'this_month');
        $growth = $this->calcEngine->calculateSalesGrowth($businessId);
        $paymentRisk = $this->calcEngine->calculatePaymentRisk($businessId);
        $inventoryRisk = $this->calcEngine->calculateInventoryRisk($businessId);

        // Recent Invoices / Activity
        $recentInvoicesCount = Invoice::where('business_id', $businessId)
            ->where('date', '>=', Carbon::now()->subDays(7))
            ->count();

        // Customer Stats
        $totalCustomersCount = Customer::where('business_id', $businessId)->count();
        $inactiveCustomersCount = Customer::where('business_id', $businessId)
            ->whereDoesntHave('invoices', fn($q) => $q->where('date', '>=', Carbon::now()->subDays(30)))
            ->count();

        $operationalRisks = [];
        if ($inventoryRisk['low_stock_count'] > 0) {
            $operationalRisks[] = "{$inventoryRisk['low_stock_count']} product(s) below low stock threshold.";
        }
        if ($paymentRisk['total_owed'] > 0) {
            $operationalRisks[] = "{$currency}" . number_format($paymentRisk['total_owed'], 2) . " uncollected customer debt.";
        }
        if ($growth['growth_pct'] < -10) {
            $operationalRisks[] = "Sales down by " . abs($growth['growth_pct']) . "% compared to last month.";
        }

        return [
            'business' => [
                'id'       => $business->id,
                'name'     => $business->name,
                'type'     => $business->type ?? 'Retail',
                'currency' => $currency,
            ],
            'financial_health' => [
                'monthly_revenue' => "{$currency}" . number_format($profitThisMonth['sales_revenue'], 2),
                'net_profit'      => "{$currency}" . number_format($profitThisMonth['net_profit'], 2),
                'profit_margin'   => "{$profitThisMonth['margin_pct']}%",
            ],
            'sales_health' => [
                'growth_trend'     => $growth['trend'],
                'growth_percentage'=> "{$growth['growth_pct']}%",
                'last_7_days_bills'=> $recentInvoicesCount,
            ],
            'inventory_health' => [
                'low_stock_items_count' => $inventoryRisk['low_stock_count'],
                'critical_items'        => array_slice($inventoryRisk['items'], 0, 3),
            ],
            'customer_health' => [
                'total_customers'   => $totalCustomersCount,
                'inactive_customers'=> $inactiveCustomersCount,
                'total_debt_owed'   => "{$currency}" . number_format($paymentRisk['total_owed'], 2),
                'debtors_count'     => $paymentRisk['debtors_count'],
            ],
            'operational_risks' => $operationalRisks,
        ];
    }
}
