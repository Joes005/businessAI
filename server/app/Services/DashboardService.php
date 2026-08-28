<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\CustomerReminder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardService
{
    /**
     * Get real-time dashboard KPIs, charts, bestsellers, activity feed, and urgent alerts.
     */
    public function getDashboardData(int $businessId): array
    {
        $today = Carbon::today();
        $startOfWeek = Carbon::now()->startOfWeek();
        $startOfMonth = Carbon::now()->startOfMonth();

        // 1. Core KPIs
        $todaySales = (float) Invoice::where('business_id', $businessId)->whereDate('date', $today)->sum('grand_total');
        $todayInvoicesCount = Invoice::where('business_id', $businessId)->whereDate('date', $today)->count();

        $thisWeekSales = (float) Invoice::where('business_id', $businessId)->where('date', '>=', $startOfWeek)->sum('grand_total');
        $thisMonthSales = (float) Invoice::where('business_id', $businessId)->where('date', '>=', $startOfMonth)->sum('grand_total');

        // Compute Month's Profit (Gross Sales Revenue - COGS of items sold this month)
        $monthItemRevenue = (float) InvoiceItem::where('business_id', $businessId)
                                                ->whereHas('invoice', fn($q) => $q->where('date', '>=', $startOfMonth))
                                                ->sum('total');
        $monthItemCOGS = (float) InvoiceItem::selectRaw('SUM(quantity * unit_cost) as total_cogs')
                                             ->where('business_id', $businessId)
                                             ->whereHas('invoice', fn($q) => $q->where('date', '>=', $startOfMonth))
                                             ->value('total_cogs') ?: 0.0;
        $thisMonthProfit = round(max(0, $monthItemRevenue - $monthItemCOGS), 2);

        $totalCashCollected = (float) Payment::where('business_id', $businessId)->sum('amount');
        $totalMoneyOwed = (float) Invoice::where('business_id', $businessId)->sum('balance_due');
        $lowStockCount = Product::where('business_id', $businessId)->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->count();

        // 2. Best-Selling Products (Top 5)
        $bestSellingProducts = InvoiceItem::select(
                                    'product_name',
                                    DB::raw('SUM(quantity) as total_qty'),
                                    DB::raw('SUM(total) as total_revenue')
                                )
                                ->where('business_id', $businessId)
                                ->groupBy('product_name')
                                ->orderByDesc('total_qty')
                                ->limit(5)
                                ->get();

        // 3. Last 7 Days Sales & Profit Visual Chart Data
        $salesChartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dateStr = $date->toDateString();
            $dayLabel = $date->format('D'); // Mon, Tue, etc.

            $dayRevenue = (float) Invoice::where('business_id', $businessId)->whereDate('date', $dateStr)->sum('grand_total');

            $dayCOGS = (float) InvoiceItem::selectRaw('SUM(quantity * unit_cost) as total_cogs')
                                           ->where('business_id', $businessId)
                                           ->whereHas('invoice', fn($q) => $q->whereDate('date', $dateStr))
                                           ->value('total_cogs') ?: 0.0;
            $dayProfit = max(0, round($dayRevenue - $dayCOGS, 2));

            $salesChartData[] = [
                'date'    => $dateStr,
                'day'     => $dayLabel,
                'revenue' => round($dayRevenue, 2),
                'profit'  => round($dayProfit, 2),
            ];
        }

        // 4. Recent Activity Stream (Latest 10 Invoices & Payments)
        $latestInvoices = Invoice::where('business_id', $businessId)
                                 ->latest('id')
                                 ->limit(5)
                                 ->get()
                                 ->map(fn($inv) => [
                                     'type'        => 'INVOICE',
                                     'title'       => "Invoice #{$inv->invoice_number}",
                                     'description' => "{$inv->customer_name} • {$inv->payment_method}",
                                     'amount'      => $inv->grand_total,
                                     'status'      => $inv->payment_status,
                                     'created_at'  => $inv->created_at->toDateTimeString(),
                                 ]);

        $latestPayments = Payment::where('business_id', $businessId)
                                 ->with('customer')
                                 ->latest('id')
                                 ->limit(5)
                                 ->get()
                                 ->map(fn($pay) => [
                                     'type'        => 'PAYMENT',
                                     'title'       => "Payment Received ({$pay->payment_method})",
                                     'description' => "Received from {$pay->customer?->name}",
                                     'amount'      => $pay->amount,
                                     'status'      => 'RECEIVED',
                                     'created_at'  => $pay->created_at->toDateTimeString(),
                                 ]);

        $recentActivity = $latestInvoices->concat($latestPayments)->sortByDesc('created_at')->values()->take(8);

        // 5. Urgent Business Alerts
        $urgentAlerts = [];

        // Low stock items
        $lowStockItems = Product::where('business_id', $businessId)
                                ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                                ->limit(5)
                                ->get();
        foreach ($lowStockItems as $item) {
            $urgentAlerts[] = [
                'type'        => 'STOCK',
                'severity'    => $item->stock_quantity <= 0 ? 'high' : 'medium',
                'title'       => "Low Stock Warning: {$item->name}",
                'description' => "Only {$item->stock_quantity} {$item->unit} remaining in inventory.",
            ];
        }

        // Today's pending debt reminders
        $todayReminders = CustomerReminder::where('business_id', $businessId)
                                          ->where('status', 'PENDING')
                                          ->whereDate('reminder_date', '<=', $today)
                                          ->with('customer')
                                          ->limit(5)
                                          ->get();
        foreach ($todayReminders as $rem) {
            $urgentAlerts[] = [
                'type'        => 'REMINDER',
                'severity'    => 'medium',
                'title'       => "Debt Collection Follow-up: {$rem->customer?->name}",
                'description' => $rem->notes ?: "Pending balance collection of {$rem->amount}",
            ];
        }

        return [
            'today_sales'           => round($todaySales, 2),
            'today_invoices_count'  => $todayInvoicesCount,
            'this_week_sales'       => round($thisWeekSales, 2),
            'this_month_sales'      => round($thisMonthSales, 2),
            'this_month_profit'     => round($thisMonthProfit, 2),
            'total_cash_collected'  => round($totalCashCollected, 2),
            'total_money_owed'      => round($totalMoneyOwed, 2),
            'low_stock_count'       => $lowStockCount,
            'best_selling_products' => $bestSellingProducts,
            'sales_chart_data'      => $salesChartData,
            'recent_activity'       => $recentActivity,
            'urgent_alerts'         => $urgentAlerts,
        ];
    }
}
