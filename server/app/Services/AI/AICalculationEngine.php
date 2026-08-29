<?php

namespace App\Services\AI;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Customer;
use Carbon\Carbon;

class AICalculationEngine
{
    /**
     * Calculate server-side verified Profit & Loss metrics for a given period.
     */
    public function calculateProfit(int $businessId, string $period = 'this_month'): array
    {
        $query = Invoice::where('business_id', $businessId);

        if ($period === 'today') {
            $query->whereDate('date', Carbon::today());
        } elseif ($period === 'this_month') {
            $query->where('date', '>=', Carbon::now()->startOfMonth());
        } elseif ($period === 'last_month') {
            $query->whereBetween('date', [
                Carbon::now()->subMonth()->startOfMonth(),
                Carbon::now()->subMonth()->endOfMonth(),
            ]);
        }

        $sales = (float) $query->sum('grand_total');

        $cogsQuery = InvoiceItem::selectRaw('SUM(quantity * unit_cost) as total_cogs')
            ->where('business_id', $businessId);

        if ($period === 'today') {
            $cogsQuery->whereHas('invoice', fn($q) => $q->whereDate('date', Carbon::today()));
        } elseif ($period === 'this_month') {
            $cogsQuery->whereHas('invoice', fn($q) => $q->where('date', '>=', Carbon::now()->startOfMonth()));
        } elseif ($period === 'last_month') {
            $cogsQuery->whereHas('invoice', fn($q) => $q->whereBetween('date', [
                Carbon::now()->subMonth()->startOfMonth(),
                Carbon::now()->subMonth()->endOfMonth(),
            ]));
        }

        $cogs = (float) ($cogsQuery->value('total_cogs') ?: 0.0);
        $profit = round($sales - $cogs, 2);
        $margin = $sales > 0 ? round(($profit / $sales) * 100, 1) : 0.0;

        return [
            'period'        => $period,
            'sales_revenue' => $sales,
            'cogs'          => $cogs,
            'net_profit'    => $profit,
            'margin_pct'    => $margin,
        ];
    }

    /**
     * Calculate period-over-period sales growth.
     */
    public function calculateSalesGrowth(int $businessId): array
    {
        $thisMonthSales = (float) Invoice::where('business_id', $businessId)
            ->where('date', '>=', Carbon::now()->startOfMonth())
            ->sum('grand_total');

        $lastMonthSales = (float) Invoice::where('business_id', $businessId)
            ->whereBetween('date', [
                Carbon::now()->subMonth()->startOfMonth(),
                Carbon::now()->subMonth()->endOfMonth(),
            ])
            ->sum('grand_total');

        $growthPct = 0.0;
        if ($lastMonthSales > 0) {
            $growthPct = round((($thisMonthSales - $lastMonthSales) / $lastMonthSales) * 100, 1);
        }

        return [
            'this_month_sales' => $thisMonthSales,
            'last_month_sales' => $lastMonthSales,
            'growth_pct'       => $growthPct,
            'trend'            => $growthPct >= 0 ? 'UP' : 'DOWN',
        ];
    }

    /**
     * Calculate customer payment risk & uncollected debts.
     */
    public function calculatePaymentRisk(int $businessId): array
    {
        $totalOwed = (float) Invoice::where('business_id', $businessId)->sum('balance_due');
        $debtorsCount = Customer::where('business_id', $businessId)
            ->whereHas('invoices', fn($q) => $q->where('balance_due', '>', 0))
            ->count();

        $overdueCount = Invoice::where('business_id', $businessId)
            ->where('balance_due', '>', 0)
            ->where('created_at', '<', Carbon::now()->subDays(15))
            ->count();

        return [
            'total_owed'     => $totalOwed,
            'debtors_count'  => $debtorsCount,
            'overdue_count'  => $overdueCount,
            'risk_level'     => $totalOwed > 50000 ? 'HIGH' : ($totalOwed > 10000 ? 'MEDIUM' : 'LOW'),
        ];
    }

    /**
     * Calculate stockout risk for low-stock items based on sales velocity.
     */
    public function calculateInventoryRisk(int $businessId): array
    {
        $lowStockItems = Product::where('business_id', $businessId)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->get();

        $riskItems = [];
        foreach ($lowStockItems as $product) {
            // Calculate 30-day velocity
            $soldLast30 = (float) InvoiceItem::where('business_id', $businessId)
                ->where('product_id', $product->id)
                ->where('created_at', '>=', Carbon::now()->subDays(30))
                ->sum('quantity');

            $dailyVelocity = $soldLast30 > 0 ? $soldLast30 / 30 : 0.1;
            $estimatedDaysRemaining = round($product->stock_quantity / $dailyVelocity, 1);

            $riskItems[] = [
                'id'                       => $product->id,
                'name'                     => $product->name,
                'current_stock'            => $product->stock_quantity,
                'low_stock_threshold'      => $product->low_stock_threshold,
                'daily_velocity'           => round($dailyVelocity, 2),
                'estimated_days_remaining' => $estimatedDaysRemaining,
            ];
        }

        return [
            'low_stock_count' => count($riskItems),
            'items'           => $riskItems,
        ];
    }
}
