<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InvoiceService
{
    /**
     * Create a complete invoice inside a database transaction.
     * Automatically updates product stock levels and logs SALE stock movements.
     */
    public function createInvoice(array $data, int $userId, int $businessId): Invoice
    {
        return DB::transaction(function () use ($data, $userId, $businessId) {
            $itemsData = $data['items'];
            if (empty($itemsData)) {
                throw new InvalidArgumentException('Invoice must contain at least one product item.');
            }

            // 1. Calculate items subtotal and verify products
            $calculatedSubtotal = 0.0;
            $processedItems = [];

            foreach ($itemsData as $itemInput) {
                $product = Product::lockForUpdate()
                                  ->where('business_id', $businessId)
                                  ->findOrFail($itemInput['product_id']);

                $quantity = (float) $itemInput['quantity'];
                if ($quantity <= 0) {
                    throw new InvalidArgumentException("Invalid quantity for product '{$product->name}'.");
                }

                $unitPrice = isset($itemInput['unit_price']) ? (float) $itemInput['unit_price'] : (float) $product->selling_price;
                $itemTotal = round($quantity * $unitPrice, 2);
                $calculatedSubtotal += $itemTotal;

                $processedItems[] = [
                    'product'    => $product,
                    'unit_price' => $unitPrice,
                    'unit_cost'  => $product->purchase_price,
                    'quantity'   => $quantity,
                    'unit'       => $product->unit,
                    'total'      => $itemTotal,
                ];
            }

            // 2. Financial Calculations
            $discountType = $data['discount_type'] ?? 'flat';
            $discountVal = (float) ($data['discount_value'] ?? 0.0);
            $discountAmount = 0.0;

            if ($discountType === 'percent') {
                $discountAmount = round(($calculatedSubtotal * min(100, max(0, $discountVal))) / 100, 2);
            } else {
                $discountAmount = round(min($calculatedSubtotal, max(0, $discountVal)), 2);
            }

            $taxableSubtotal = max(0.0, $calculatedSubtotal - $discountAmount);
            $taxPercent = (float) ($data['tax_percent'] ?? 0.0);
            $taxAmount = round(($taxableSubtotal * min(100, max(0, $taxPercent))) / 100, 2);

            $grandTotal = round($taxableSubtotal + $taxAmount, 2);
            $amountPaid = isset($data['amount_paid']) ? (float) $data['amount_paid'] : $grandTotal;
            $balanceDue = round(max(0.0, $grandTotal - $amountPaid), 2);

            // Determine Payment Status
            $paymentStatus = 'PAID';
            if ($amountPaid <= 0) {
                $paymentStatus = 'UNPAID';
            } elseif ($balanceDue > 0) {
                $paymentStatus = 'PARTIAL';
            }

            // 3. Create Invoice Record
            $invoice = Invoice::create([
                'business_id'     => $businessId,
                'customer_id'     => $data['customer_id'] ?? null,
                'user_id'         => $userId,
                'customer_name'   => $data['customer_name'] ?? 'Walk-in Customer',
                'customer_phone'  => $data['customer_phone'] ?? null,
                'date'            => $data['date'] ?? now()->toDateString(),
                'subtotal'        => $calculatedSubtotal,
                'discount_type'   => $discountType,
                'discount_value'  => $discountVal,
                'discount_amount' => $discountAmount,
                'tax_percent'     => $taxPercent,
                'tax_amount'      => $taxAmount,
                'grand_total'     => $grandTotal,
                'amount_paid'     => $amountPaid,
                'balance_due'     => $balanceDue,
                'payment_method'  => strtoupper($data['payment_method'] ?? 'CASH'),
                'payment_status'  => $paymentStatus,
                'notes'           => $data['notes'] ?? null,
            ]);

            // 4. Create Invoice Items & Stock Movements
            foreach ($processedItems as $pi) {
                $product = $pi['product'];
                $qty = $pi['quantity'];

                InvoiceItem::create([
                    'business_id'     => $businessId,
                    'invoice_id'      => $invoice->id,
                    'product_id'      => $product->id,
                    'product_name'    => $product->name,
                    'unit'            => $pi['unit'],
                    'unit_price'      => $pi['unit_price'],
                    'unit_cost'       => $pi['unit_cost'],
                    'quantity'        => $qty,
                    'discount_amount' => 0.00,
                    'total'           => $pi['total'],
                ]);

                // Update Stock Quantity
                $stockBefore = $product->stock_quantity;
                $stockAfter = $stockBefore - $qty;
                $product->update(['stock_quantity' => $stockAfter]);

                // Log SALE Stock Movement
                StockMovement::create([
                    'business_id'   => $businessId,
                    'product_id'    => $product->id,
                    'user_id'       => $userId,
                    'type'          => 'SALE',
                    'quantity'      => -$qty,
                    'unit_cost'     => $pi['unit_cost'],
                    'stock_before'  => $stockBefore,
                    'stock_after'   => $stockAfter,
                    'reference_no'  => $invoice->invoice_number,
                    'notes'         => "Sale via Invoice #{$invoice->invoice_number}",
                ]);
            }

            return $invoice->load(['items.product', 'business', 'user']);
        });
    }
}
