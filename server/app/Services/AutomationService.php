<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\AutomationLog;

class AutomationService
{
    /**
     * Run automated business checks for low stock and customer debt alerts.
     */
    public function runAutomatedChecks(int $businessId): array
    {
        $createdLogs = [];

        // 1. Audit Low Stock Products
        $lowStockProducts = Product::where('business_id', $businessId)
                                   ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                                   ->get();

        foreach ($lowStockProducts as $prod) {
            $msg = "Low stock alert: '{$prod->name}' has only {$prod->stock_quantity} {$prod->unit} left in inventory.";

            $log = AutomationLog::create([
                'business_id'  => $businessId,
                'trigger_type' => 'LOW_STOCK_ALERT',
                'recipient'    => 'Store Manager',
                'message'      => $msg,
                'status'       => 'SENT',
            ]);
            $createdLogs[] = $log;
        }

        // 2. Audit Customer Outstanding Debt
        $debtors = Customer::where('business_id', $businessId)
                           ->whereHas('invoices', fn($q) => $q->where('balance_due', '>', 0))
                           ->get();

        foreach ($debtors as $debtor) {
            $msg = "Debt reminder queued: '{$debtor->name}' owes ₹{$debtor->outstanding_amount}.";

            $log = AutomationLog::create([
                'business_id'  => $businessId,
                'trigger_type' => 'DEBT_REMINDER',
                'recipient'    => $debtor->name,
                'message'      => $msg,
                'status'       => 'SENT',
            ]);
            $createdLogs[] = $log;
        }

        return [
            'logs_count' => count($createdLogs),
            'logs'       => $createdLogs,
        ];
    }

    /**
     * Retrieve automation logs history.
     */
    public function getLogs(int $businessId): array
    {
        $logs = AutomationLog::where('business_id', $businessId)->latest()->limit(50)->get();

        return [
            'logs' => $logs,
        ];
    }
}
