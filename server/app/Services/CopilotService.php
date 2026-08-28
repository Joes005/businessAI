<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Customer;
use App\Models\CustomerReminder;
use Illuminate\Support\Str;
use Carbon\Carbon;

class CopilotService
{
    /**
     * Interpret natural language query, fetch live business data, and return structured AI response.
     */
    public function processQuery(string $prompt, int $businessId): array
    {
        $normalized = Str::lower($prompt);
        $currency = '₹';

        // 1. Intent: Today's Sales
        if (Str::contains($normalized, ['today', 'daily sales', 'sales today', 'sell today'])) {
            $today = Carbon::today();
            $sales = (float) Invoice::where('business_id', $businessId)->whereDate('date', $today)->sum('grand_total');
            $count = Invoice::where('business_id', $businessId)->whereDate('date', $today)->count();

            return [
                'intent'            => 'SALES_TODAY',
                'answer'            => "Today you have generated **{$currency}" . number_format($sales, 2) . "** in total sales across **{$count}** bills.",
                'metrics'           => [
                    ['label' => "Today's Sales", 'value' => "{$currency}" . number_format($sales, 2)],
                    ['label' => "Bills Count", 'value' => (string) $count],
                ],
                'data_type'         => 'summary',
                'data'              => null,
                'suggested_actions' => [
                    ['label' => '⚡ Open POS Counter', 'route' => '/billing'],
                    ['label' => '📊 View Sales Report', 'route' => '/reports'],
                ],
            ];
        }

        // 2. Intent: Month's Sales
        if (Str::contains($normalized, ['this month', 'month sales', 'monthly sales', 'revenue this month'])) {
            $startOfMonth = Carbon::now()->startOfMonth();
            $sales = (float) Invoice::where('business_id', $businessId)->where('date', '>=', $startOfMonth)->sum('grand_total');
            $count = Invoice::where('business_id', $businessId)->where('date', '>=', $startOfMonth)->count();

            return [
                'intent'            => 'SALES_MONTH',
                'answer'            => "This month's total sales revenue is **{$currency}" . number_format($sales, 2) . "** from **{$count}** invoices.",
                'metrics'           => [
                    ['label' => 'Monthly Revenue', 'value' => "{$currency}" . number_format($sales, 2)],
                    ['label' => 'Invoices Count', 'value' => (string) $count],
                ],
                'data_type'         => 'summary',
                'data'              => null,
                'suggested_actions' => [
                    ['label' => '📊 Full Sales Report', 'route' => '/reports'],
                ],
            ];
        }

        // 3. Intent: Profit & Margin
        if (Str::contains($normalized, ['profit', 'margin', 'how much profit', 'earnings', 'pnl', 'p&l'])) {
            $startOfMonth = Carbon::now()->startOfMonth();
            $sales = (float) Invoice::where('business_id', $businessId)->where('date', '>=', $startOfMonth)->sum('grand_total');

            $cogs = (float) InvoiceItem::selectRaw('SUM(quantity * unit_cost) as total_cogs')
                                       ->where('business_id', $businessId)
                                       ->whereHas('invoice', fn($q) => $q->where('date', '>=', $startOfMonth))
                                       ->value('total_cogs') ?: 0.0;

            $profit = max(0, round($sales - $cogs, 2));
            $margin = $sales > 0 ? round(($profit / $sales) * 100, 1) : 0;

            return [
                'intent'            => 'PROFIT',
                'answer'            => "This month, your business earned a net gross profit of **{$currency}" . number_format($profit, 2) . "** with a profit margin of **{$margin}%**.",
                'metrics'           => [
                    ['label' => 'Sales Revenue', 'value' => "{$currency}" . number_format($sales, 2)],
                    ['label' => 'Cost of Goods (COGS)', 'value' => "{$currency}" . number_format($cogs, 2)],
                    ['label' => 'Gross Profit', 'value' => "{$currency}" . number_format($profit, 2)],
                    ['label' => 'Margin %', 'value' => "{$margin}%"],
                ],
                'data_type'         => 'summary',
                'data'              => null,
                'suggested_actions' => [
                    ['label' => '💰 View P&L Statement', 'route' => '/reports'],
                ],
            ];
        }

        // 4. Intent: Low Stock & Restock Alerts
        if (Str::contains($normalized, ['low stock', 'out of stock', 'stock', 'restock', 'inventory low', 'items low'])) {
            $lowStockItems = Product::where('business_id', $businessId)
                                    ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                                    ->with('category')
                                    ->get();

            $count = $lowStockItems->count();
            if ($count === 0) {
                return [
                    'intent'            => 'LOW_STOCK',
                    'answer'            => "Great news! All products are currently well-stocked. No items have reached low stock threshold.",
                    'metrics'           => [['label' => 'Low Stock Items', 'value' => '0']],
                    'data_type'         => 'text',
                    'data'              => null,
                    'suggested_actions' => [['label' => '📦 View Product Inventory', 'route' => '/products']],
                ];
            }

            return [
                'intent'            => 'LOW_STOCK',
                'answer'            => "You have **{$count}** product(s) running low or out of stock that require restocking.",
                'metrics'           => [['label' => 'Items Needing Restock', 'value' => (string) $count]],
                'data_type'         => 'table',
                'data'              => $lowStockItems->map(fn($p) => [
                    'name'                => $p->name,
                    'category'            => $p->category?->name || 'Uncategorized',
                    'stock_quantity'      => $p->stock_quantity,
                    'unit'                => $p->unit,
                    'low_stock_threshold' => $p->low_stock_threshold,
                ]),
                'suggested_actions' => [
                    ['label' => '📦 Adjust Stock Levels', 'route' => '/products'],
                ],
            ];
        }

        // 5. Intent: Customers & Debts ("Who owes me money?")
        if (Str::contains($normalized, ['owe', 'money', 'debt', 'debtors', 'who owes', 'collect', 'receivable'])) {
            $debtors = Customer::where('business_id', $businessId)
                               ->whereHas('invoices', fn($q) => $q->where('balance_due', '>', 0))
                               ->get();

            $totalOwed = (float) Invoice::where('business_id', $businessId)->sum('balance_due');

            if ($debtors->count() === 0) {
                return [
                    'intent'            => 'DEBTORS',
                    'answer'            => "All customer accounts are clear! There are currently no pending debts or money to collect.",
                    'metrics'           => [['label' => 'Money Owed', 'value' => "{$currency}0.00"]],
                    'data_type'         => 'text',
                    'data'              => null,
                    'suggested_actions' => [['label' => '👥 View Customer Directory', 'route' => '/customers']],
                ];
            }

            return [
                'intent'            => 'DEBTORS',
                'answer'            => "You have **{$debtors->count()}** customer(s) owing a combined total of **{$currency}" . number_format($totalOwed, 2) . "**.",
                'metrics'           => [
                    ['label' => 'Total Debt Owed', 'value' => "{$currency}" . number_format($totalOwed, 2)],
                    ['label' => 'Debtors Count', 'value' => (string) $debtors->count()],
                ],
                'data_type'         => 'table',
                'data'              => $debtors->map(fn($c) => [
                    'name'               => $c->name,
                    'phone'              => $c->phone || 'N/A',
                    'outstanding_amount' => $c->outstanding_amount,
                ]),
                'suggested_actions' => [
                    ['label' => '💵 Record Collection', 'route' => '/customers'],
                ],
            ];
        }

        // 6. Intent: Best-Selling Products
        if (Str::contains($normalized, ['bestseller', 'best selling', 'top product', 'top items', 'most sold'])) {
            $bestsellers = InvoiceItem::select('product_name', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(total) as total_revenue'))
                                      ->where('business_id', $businessId)
                                      ->groupBy('product_name')
                                      ->orderByDesc('total_qty')
                                      ->limit(5)
                                      ->get();

            return [
                'intent'            => 'BESTSELLERS',
                'answer'            => "Here are your top best-selling products ranked by sales volume:",
                'metrics'           => [],
                'data_type'         => 'list',
                'data'              => $bestsellers,
                'suggested_actions' => [
                    ['label' => '⚡ Billing Counter', 'route' => '/billing'],
                    ['label' => '📊 Full Sales Report', 'route' => '/reports'],
                ],
            ];
        }

        // Default / Guidance Response
        return [
            'intent'            => 'HELP',
            'answer'            => "I'm your **AI Business Copilot**! You can ask me natural questions about your business, sales, profit, stock, and debts. Try asking one of the sample prompts below:",
            'metrics'           => [],
            'data_type'         => 'text',
            'data'              => null,
            'suggested_actions' => [
                ['label' => 'How much did I sell today?'],
                ['label' => 'Who owes me money?'],
                ['label' => 'Which items are low in stock?'],
                ['label' => 'What is my profit this month?'],
            ],
        ];
    }

    /**
     * Get proactive business health alerts and suggestions.
     */
    public function getProactiveInsights(int $businessId): array
    {
        $insights = [];

        // Check low stock count
        $lowStockCount = Product::where('business_id', $businessId)
                                ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                                ->count();
        if ($lowStockCount > 0) {
            $insights[] = [
                'type'        => 'STOCK_WARNING',
                'title'       => 'Inventory Alert',
                'message'     => "{$lowStockCount} product(s) are running low on stock and need restock.",
                'action_label'=> 'Manage Products',
                'route'       => '/products',
            ];
        }

        // Check customer debt count
        $totalOwed = (float) Invoice::where('business_id', $businessId)->sum('balance_due');
        if ($totalOwed > 0) {
            $insights[] = [
                'type'        => 'DEBT_WARNING',
                'title'       => 'Pending Receivables',
                'message'     => "You have ₹" . number_format($totalOwed, 2) . " in uncollected customer debt.",
                'action_label'=> 'Collect Payments',
                'route'       => '/customers',
            ];
        }

        return $insights;
    }
}
