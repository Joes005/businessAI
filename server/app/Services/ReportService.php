<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Sales Summary Report by Date Range.
     */
    public function getSalesReport(int $businessId, string $startDate, string $endDate): array
    {
        $query = Invoice::where('business_id', $businessId)
                        ->whereBetween('date', [$startDate, $endDate]);

        $totalBills = $query->count();
        $grossRevenue = (float) $query->sum('subtotal');
        $totalDiscounts = (float) $query->sum('discount_amount');
        $totalTax = (float) $query->sum('tax_amount');
        $netRevenue = (float) $query->sum('grand_total');

        // Breakdown by Payment Method
        $paymentMethods = Invoice::select('payment_method', DB::raw('COUNT(*) as bill_count'), DB::raw('SUM(grand_total) as total_amount'))
                                 ->where('business_id', $businessId)
                                 ->whereBetween('date', [$startDate, $endDate])
                                 ->groupBy('payment_method')
                                 ->get();

        // Invoices Breakdown List
        $invoicesList = Invoice::where('business_id', $businessId)
                               ->whereBetween('date', [$startDate, $endDate])
                               ->latest('date')
                               ->get();

        return [
            'start_date'       => $startDate,
            'end_date'         => $endDate,
            'total_bills'      => $totalBills,
            'gross_revenue'    => round($grossRevenue, 2),
            'total_discounts'  => round($totalDiscounts, 2),
            'total_tax'        => round($totalTax, 2),
            'net_revenue'      => round($netRevenue, 2),
            'payment_methods'  => $paymentMethods,
            'invoices'         => $invoicesList,
        ];
    }

    /**
     * Profit & Loss Statement by Date Range.
     */
    public function getProfitLossReport(int $businessId, string $startDate, string $endDate): array
    {
        $salesRevenue = (float) Invoice::where('business_id', $businessId)
                                       ->whereBetween('date', [$startDate, $endDate])
                                       ->sum('grand_total');

        // Cost of Goods Sold (COGS)
        $cogs = (float) InvoiceItem::selectRaw('SUM(quantity * unit_cost) as total_cogs')
                                   ->where('business_id', $businessId)
                                   ->whereHas('invoice', fn($q) => $q->whereBetween('date', [$startDate, $endDate]))
                                   ->value('total_cogs') ?: 0.0;

        $grossProfit = round($salesRevenue - $cogs, 2);
        $marginPercent = $salesRevenue > 0 ? round(($grossProfit / $salesRevenue) * 100, 2) : 0.0;

        return [
            'start_date'     => $startDate,
            'end_date'       => $endDate,
            'sales_revenue'  => round($salesRevenue, 2),
            'cogs'           => round($cogs, 2),
            'gross_profit'   => $grossProfit,
            'margin_percent' => $marginPercent,
        ];
    }

    /**
     * Inventory Valuation Report.
     */
    public function getInventoryValuationReport(int $businessId): array
    {
        $totalItems = Product::where('business_id', $businessId)->count();
        $totalQuantity = (float) Product::where('business_id', $businessId)->sum('stock_quantity');

        $totalPurchaseValue = (float) Product::selectRaw('SUM(stock_quantity * purchase_price) as total_val')
                                             ->where('business_id', $businessId)
                                             ->value('total_val') ?: 0.0;

        $totalSellingValue = (float) Product::selectRaw('SUM(stock_quantity * selling_price) as total_val')
                                            ->where('business_id', $businessId)
                                            ->value('total_val') ?: 0.0;

        $potentialProfit = round($totalSellingValue - $totalPurchaseValue, 2);

        $products = Product::where('business_id', $businessId)
                           ->with('category')
                           ->latest('id')
                           ->get();

        return [
            'total_items'          => $totalItems,
            'total_quantity'       => round($totalQuantity, 2),
            'total_purchase_value' => round($totalPurchaseValue, 2),
            'total_selling_value'  => round($totalSellingValue, 2),
            'potential_profit'     => $potentialProfit,
            'products'             => $products,
        ];
    }

    /**
     * Customer Debtors ("Accounts Receivable") Report.
     */
    public function getDebtorsReport(int $businessId): array
    {
        $debtors = Customer::where('business_id', $businessId)
                           ->whereHas('invoices', fn($q) => $q->where('balance_due', '>', 0))
                           ->get();

        $totalOutstanding = (float) Invoice::where('business_id', $businessId)->sum('balance_due');

        return [
            'total_debtors'     => $debtors->count(),
            'total_outstanding' => round($totalOutstanding, 2),
            'debtors'           => $debtors,
        ];
    }
}
