<?php

namespace App\Services\AI;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Customer;
use App\Models\AiToolLog;
use App\Models\AiAction;
use App\Models\CustomerReminder;
use Carbon\Carbon;
use Illuminate\Support\Str;

class AIActionExecutor
{
    protected AICalculationEngine $calcEngine;

    public function __construct(AICalculationEngine $calcEngine)
    {
        $this->calcEngine = $calcEngine;
    }

    /**
     * Execute tool safely with business_id multi-tenant verification and audit logging.
     */
    public function execute(string $toolName, array $args, int $businessId, ?int $userId = null, string $prompt = ''): array
    {
        $startTime = microtime(true);
        $success = true;
        $errorMessage = null;
        $result = [];

        $normalizedPrompt = Str::lower($prompt);
        $isTanglish = Str::contains($normalizedPrompt, [
            'innaiku', 'evlo', 'kadan', 'yaaru', 'kammiya', 'taranum', 'labam', 'epdi', 'vanakkam', 'irukka', 'indha'
        ]);

        try {
            switch ($toolName) {
                case 'get_today_sales':
                    $today = Carbon::today();
                    $sales = (float) Invoice::where('business_id', $businessId)->whereDate('date', $today)->sum('grand_total');
                    $count = Invoice::where('business_id', $businessId)->whereDate('date', $today)->count();

                    $answer = $isTanglish
                        ? "Innaiku ungalukku **₹" . number_format($sales, 2) . "** total sales aayirukku across **{$count}** bills. 📊"
                        : "Today you generated **₹" . number_format($sales, 2) . "** total sales across **{$count}** bills.";

                    $result = [
                        'intent'  => 'SALES_TODAY',
                        'sales'   => $sales,
                        'count'   => $count,
                        'answer'  => $answer,
                        'metrics' => [
                            ['label' => "Today's Sales", 'value' => "₹" . number_format($sales, 2)],
                            ['label' => 'Bills Count', 'value' => (string) $count],
                        ],
                    ];
                    break;

                case 'get_sales_summary':
                    $period = $args['period'] ?? 'this_month';
                    $salesData = $this->calcEngine->calculateProfit($businessId, $period);

                    $answer = $isTanglish
                        ? "Indha month ungaludaiya total sales revenue **₹" . number_format($salesData['sales_revenue'], 2) . "**. 📊"
                        : "Total sales revenue for " . str_replace('_', ' ', $period) . " is **₹" . number_format($salesData['sales_revenue'], 2) . "**.";

                    $result = [
                        'intent'  => 'SALES_SUMMARY',
                        'period'  => $period,
                        'sales'   => $salesData['sales_revenue'],
                        'answer'  => $answer,
                        'metrics' => [
                            ['label' => 'Sales Revenue', 'value' => "₹" . number_format($salesData['sales_revenue'], 2)],
                        ],
                    ];
                    break;

                case 'calculate_profit':
                    $period = $args['period'] ?? 'this_month';
                    $profitData = $this->calcEngine->calculateProfit($businessId, $period);

                    $answer = $isTanglish
                        ? "Indha month unga business net gross profit **₹" . number_format($profitData['net_profit'], 2) . "** with profit margin **{$profitData['margin_pct']}%**. 💰"
                        : "For " . str_replace('_', ' ', $period) . ", your gross profit is **₹" . number_format($profitData['net_profit'], 2) . "** with a profit margin of **{$profitData['margin_pct']}%**.";

                    $result = [
                        'intent'  => 'PROFIT',
                        'data'    => $profitData,
                        'answer'  => $answer,
                        'metrics' => [
                            ['label' => 'Sales Revenue', 'value' => "₹" . number_format($profitData['sales_revenue'], 2)],
                            ['label' => 'Cost of Goods (COGS)', 'value' => "₹" . number_format($profitData['cogs'], 2)],
                            ['label' => 'Net Gross Profit', 'value' => "₹" . number_format($profitData['net_profit'], 2)],
                            ['label' => 'Profit Margin', 'value' => "{$profitData['margin_pct']}%"],
                        ],
                    ];
                    break;

                case 'get_low_stock_products':
                    $risk = $this->calcEngine->calculateInventoryRisk($businessId);

                    if ($isTanglish) {
                        $answer = $risk['low_stock_count'] > 0
                            ? "Boss! Ungalukku **{$risk['low_stock_count']}** items stock romba kammiya irukku. Restock panna vendiyathu irukku. 📦"
                            : "Super news boss! Ellam products-um nalla stock irukku! 👍";
                    } else {
                        $answer = $risk['low_stock_count'] > 0
                            ? "You have **{$risk['low_stock_count']}** product(s) running low or out of stock that require restocking."
                            : "Great news! All products are currently well-stocked.";
                    }

                    $result = [
                        'intent'    => 'LOW_STOCK',
                        'data_type' => 'table',
                        'data'      => $risk['items'],
                        'answer'    => $answer,
                        'metrics'   => [
                            ['label' => 'Low Stock Items', 'value' => (string) $risk['low_stock_count']],
                        ],
                    ];
                    break;

                case 'get_pending_payments':
                    $debtors = Customer::where('business_id', $businessId)
                        ->whereHas('invoices', fn($q) => $q->where('balance_due', '>', 0))
                        ->get();
                    $totalOwed = (float) Invoice::where('business_id', $businessId)->sum('balance_due');

                    if ($isTanglish) {
                        $answer = $debtors->count() > 0
                            ? "Ungalukku **{$debtors->count()}** customers thara vendiya kadan thogai மொத்தம் **₹" . number_format($totalOwed, 2) . "**. 👥"
                            : "Super boss! Kadan edhum baaki illai, ellam customer payment clear aayirukku!";
                    } else {
                        $answer = $debtors->count() > 0
                            ? "You have **{$debtors->count()}** customer(s) owing a combined total of **₹" . number_format($totalOwed, 2) . "**."
                            : "All customer accounts are clear! There are currently no pending debts.";
                    }

                    $result = [
                        'intent'    => 'DEBTORS',
                        'data_type' => 'table',
                        'data'      => $debtors->map(fn($c) => [
                            'name'               => $c->name,
                            'phone'              => $c->phone ?: 'N/A',
                            'outstanding_amount' => $c->outstanding_amount,
                        ])->toArray(),
                        'answer'    => $answer,
                        'metrics'   => [
                            ['label' => 'Total Uncollected Debt', 'value' => "₹" . number_format($totalOwed, 2)],
                            ['label' => 'Debtors Count', 'value' => (string) $debtors->count()],
                        ],
                    ];
                    break;

                case 'get_top_products':
                    $limit = $args['limit'] ?? 5;
                    $bestsellers = InvoiceItem::select('product_name', \DB::raw('SUM(quantity) as total_qty'), \DB::raw('SUM(total) as total_revenue'))
                        ->where('business_id', $businessId)
                        ->groupBy('product_name')
                        ->orderByDesc('total_qty')
                        ->limit($limit)
                        ->get();

                    $answer = $isTanglish
                        ? "Unga store-la அதிகமா சேல் ஆன Top best-selling products list idho:"
                        : "Here are your top best-selling products ranked by sales volume:";

                    $result = [
                        'intent'    => 'BESTSELLERS',
                        'data_type' => 'list',
                        'data'      => $bestsellers->toArray(),
                        'answer'    => $answer,
                    ];
                    break;

                case 'prepare_payment_reminder':
                    $debtors = Customer::where('business_id', $businessId)
                        ->whereHas('invoices', fn($q) => $q->where('balance_due', '>', 0))
                        ->get();

                    $action = AiAction::create([
                        'business_id'   => $businessId,
                        'user_id'       => $userId,
                        'action_type'   => 'send_payment_reminders',
                        'description'   => "Send automated payment reminder messages to {$debtors->count()} overdue customer(s).",
                        'payload'       => ['debtors' => $debtors->pluck('id')->toArray()],
                        'risk_level'    => 'HIGH_RISK_WRITE',
                        'status'        => 'PENDING',
                    ]);

                    $result = [
                        'intent'         => 'ACTION_CONFIRMATION_REQUIRED',
                        'requires_action'=> true,
                        'action_id'      => $action->id,
                        'answer'         => "I found {$debtors->count()} customer(s) with overdue payments. I have prepared payment reminders for them.\n\nWould you like me to send these reminders?",
                        'preview'        => [
                            'action_type' => 'Payment Reminders',
                            'recipients'  => $debtors->pluck('name')->toArray(),
                            'risk_level'  => 'HIGH_RISK_WRITE',
                        ],
                    ];
                    break;

                case 'create_customer':
                    $customer = Customer::create([
                        'business_id' => $businessId,
                        'name'        => $args['name'],
                        'phone'       => $args['phone'] ?? null,
                        'email'       => $args['email'] ?? null,
                    ]);

                    $result = [
                        'intent' => 'CREATE_CUSTOMER',
                        'answer' => "Customer **{$customer->name}** has been successfully created.",
                        'data'   => $customer->toArray(),
                    ];
                    break;

                default:
                    $result = [
                        'intent' => 'UNKNOWN_TOOL',
                        'answer' => "The tool {$toolName} is not recognized.",
                    ];
                    break;
            }
        } catch (\Throwable $e) {
            $success = false;
            $errorMessage = $e->getMessage();
            $result = [
                'error'  => true,
                'answer' => "Could not execute tool: " . $e->getMessage(),
            ];
        } finally {
            $executionTimeMs = (int) round((microtime(true) - $startTime) * 1000);
            AiToolLog::create([
                'business_id'       => $businessId,
                'user_id'           => $userId,
                'tool_name'         => $toolName,
                'arguments'         => $args,
                'execution_time_ms' => $executionTimeMs,
                'success'           => $success,
                'error_message'     => $errorMessage,
            ]);
        }

        return $result;
    }
}
