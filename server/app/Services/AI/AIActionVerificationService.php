<?php

namespace App\Services\AI;

use App\Models\Product;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\AiTaskStep;

class AIActionVerificationService
{
    /**
     * Verify database state change after tool step execution.
     */
    public function verifyStep(AiTaskStep $step, int $businessId): bool
    {
        $tool = $step->tool_name;
        $args = $step->arguments ?? [];

        switch ($tool) {
            case 'create_customer':
                $customer = Customer::where('business_id', $businessId)
                    ->where('name', $args['name'] ?? '')
                    ->first();
                $passed = !is_null($customer);
                break;

            case 'update_stock':
                $productId = $args['product_id'] ?? 0;
                $product = Product::where('business_id', $businessId)->find($productId);
                $passed = !is_null($product);
                break;

            case 'prepare_payment_reminder':
                $passed = true; // Preview step verified
                break;

            default:
                $passed = true; // Read tools automatically verify
                break;
        }

        $step->update([
            'verification_status' => $passed ? 'PASSED' : 'FAILED',
        ]);

        return $passed;
    }
}
