<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\Category;
use App\Models\Product;
use App\Models\Invoice;

class BillingInvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_billing_invoice_creation_and_atomic_stock_deduction_flow(): void
    {
        // 1. Create Business & Owner User
        $user = User::create([
            'name'     => 'POS Cashier',
            'email'    => 'cashier@pos.com',
            'password' => bcrypt('password123'),
        ]);

        $business = Business::create([
            'name'     => 'Metro POS Retail',
            'type'     => 'retail',
            'currency' => 'INR',
        ]);

        $business->users()->attach($user->id, ['role' => 'owner']);
        $user->update(['current_business_id' => $business->id]);

        $token = $user->createToken('auth_token')->plainTextToken;

        // 2. Create Product with initial stock of 10 units
        $product = Product::create([
            'business_id'         => $business->id,
            'name'                => 'Chocolate Biscuit Pack',
            'sku'                 => 'BIS-CHOC-01',
            'unit'                => 'pkt',
            'purchase_price'      => 20.00,
            'selling_price'       => 30.00,
            'stock_quantity'      => 10.00,
            'low_stock_threshold' => 2.00,
        ]);

        // 3. Submit Invoice Creation (Buy 3 packets of biscuits @ ₹30 = ₹90, Flat ₹10 Discount, 5% Tax)
        $invoiceRes = $this->withHeader('Authorization', "Bearer {$token}")
                           ->postJson('/api/v1/invoices', [
                               'customer_name'  => 'Amit Patel',
                               'customer_phone' => '+919876500000',
                               'date'           => now()->toDateString(),
                               'items'          => [
                                   [
                                       'product_id' => $product->id,
                                       'quantity'   => 3,
                                       'unit_price' => 30.00,
                                   ],
                               ],
                               'discount_type'  => 'flat',
                               'discount_value' => 10.00,
                               'tax_percent'    => 5.00,
                               'amount_paid'    => 84.00,
                               'payment_method' => 'UPI',
                           ]);

        $invoiceRes->assertStatus(201)
                   ->assertJsonPath('success', true)
                   ->assertJsonPath('data.invoice.invoice_number', 'INV-10001')
                   ->assertJsonPath('data.invoice.subtotal', 90)
                   ->assertJsonPath('data.invoice.discount_amount', 10)
                   ->assertJsonPath('data.invoice.tax_amount', 4) // (90 - 10) * 0.05 = 4
                   ->assertJsonPath('data.invoice.grand_total', 84) // 80 + 4 = 84
                   ->assertJsonPath('data.invoice.payment_status', 'PAID');

        // 4. Verify Product Stock Automatically Deducted (10 - 3 = 7)
        $product->refresh();
        $this->assertEquals(7.00, $product->stock_quantity);

        // 5. Verify SALE Stock Movement Logged
        $this->assertDatabaseHas('stock_movements', [
            'product_id'   => $product->id,
            'type'         => 'SALE',
            'quantity'     => -3.00,
            'stock_before' => 10.00,
            'stock_after'  => 7.00,
            'reference_no' => 'INV-10001',
        ]);

        // 6. Verify Invoices Index API
        $indexRes = $this->withHeader('Authorization', "Bearer {$token}")
                         ->getJson('/api/v1/invoices');

        $indexRes->assertStatus(200)
                 ->assertJsonPath('success', true)
                 ->assertJsonCount(1, 'data.invoices.data');
    }
}
