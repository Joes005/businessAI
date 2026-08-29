<?php

namespace App\Services\AI;

use App\Models\Business;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Customer;
use Carbon\Carbon;

class AIContextBuilder
{
    /**
     * Build intelligent, targeted context prompt for the AI Orchestrator.
     */
    public function buildContext(int $businessId, string $query = ''): array
    {
        $business = Business::find($businessId);
        if (!$business) {
            return [];
        }

        $currency = $business->currency === 'USD' ? '$' : ($business->currency === 'EUR' ? '€' : '₹');

        $baseContext = [
            'business_name' => $business->name,
            'business_type' => $business->type ?? 'Retail',
            'currency'      => $currency,
            'location'      => $business->address ?? 'Not specified',
        ];

        // Fetch overall summary metrics
        $todaySales = (float) Invoice::where('business_id', $businessId)
            ->whereDate('date', Carbon::today())
            ->sum('grand_total');

        $monthSales = (float) Invoice::where('business_id', $businessId)
            ->where('date', '>=', Carbon::now()->startOfMonth())
            ->sum('grand_total');

        $totalOwed = (float) Invoice::where('business_id', $businessId)
            ->sum('balance_due');

        $lowStockCount = Product::where('business_id', $businessId)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->count();

        $metrics = [
            'today_sales'     => "{$currency}" . number_format($todaySales, 2),
            'month_sales'     => "{$currency}" . number_format($monthSales, 2),
            'uncollected_debt'=> "{$currency}" . number_format($totalOwed, 2),
            'low_stock_count' => $lowStockCount,
        ];

        return [
            'business_info' => $baseContext,
            'quick_metrics' => $metrics,
        ];
    }
}
