<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Invoice;

class CustomerPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_ledger_and_payment_collection_flow(): void
    {
        // 1. Create Business & Owner User
        $user = User::create([
            'name'     => 'Store Manager',
            'email'    => 'manager@store.com',
            'password' => bcrypt('password123'),
        ]);

        $business = Business::create([
            'name'     => 'Super Mart',
            'type'     => 'retail',
            'currency' => 'INR',
        ]);

        $business->users()->attach($user->id, ['role' => 'owner']);
        $user->update(['current_business_id' => $business->id]);

        $token = $user->createToken('auth_token')->plainTextToken;

        // 2. Create Customer
        $custRes = $this->withHeader('Authorization', "Bearer {$token}")
                        ->postJson('/api/v1/customers', [
                            'name'  => 'Arun Kumar',
                            'phone' => '+919876543210',
                            'email' => 'arun@example.com',
                        ]);

        $custRes->assertStatus(201)
                ->assertJsonPath('success', true);

        $customerId = $custRes->json('data.customer.id');

        // 3. Create Product & Invoice for Arun (Grand Total ₹5,000, Paid ₹2,000, Balance ₹3,000)
        $product = Product::create([
            'business_id'         => $business->id,
            'name'                => 'Executive Desk Chair',
            'selling_price'       => 5000.00,
            'purchase_price'      => 3500.00,
            'stock_quantity'      => 5.00,
            'low_stock_threshold' => 1.00,
        ]);

        $invoiceRes = $this->withHeader('Authorization', "Bearer {$token}")
                           ->postJson('/api/v1/invoices', [
                               'customer_id'    => $customerId,
                               'customer_name'  => 'Arun Kumar',
                               'customer_phone' => '+919876543210',
                               'date'           => now()->toDateString(),
                               'items'          => [
                                   [
                                       'product_id' => $product->id,
                                       'quantity'   => 1,
                                       'unit_price' => 5000.00,
                                   ],
                               ],
                               'amount_paid'    => 2000.00,
                               'payment_method' => 'CREDIT',
                           ]);

        $invoiceRes->assertStatus(201)
                   ->assertJsonPath('data.invoice.payment_status', 'PARTIAL')
                   ->assertJsonPath('data.invoice.balance_due', 3000);

        $invoiceId = $invoiceRes->json('data.invoice.id');

        // 4. Check Customer Ledger Attributes
        $ledgerRes = $this->withHeader('Authorization', "Bearer {$token}")
                          ->getJson("/api/v1/customers/{$customerId}/ledger");

        $ledgerRes->assertStatus(200)
                  ->assertJsonPath('data.total_purchased', 5000)
                  ->assertJsonPath('data.total_paid', 2000)
                  ->assertJsonPath('data.outstanding_amount', 3000);

        // 5. Record Payment of ₹2,000
        $payRes = $this->withHeader('Authorization', "Bearer {$token}")
                       ->postJson('/api/v1/payments', [
                           'customer_id'    => $customerId,
                           'invoice_id'     => $invoiceId,
                           'amount'         => 2000.00,
                           'payment_method' => 'UPI',
                           'reference_no'   => 'UPI-888999',
                       ]);

        $payRes->assertStatus(201)
               ->assertJsonPath('success', true)
               ->assertJsonPath('data.payment.payment_number', 'PAY-10001');

        // 6. Verify Customer Outstanding Balance Updated to ₹1,000
        $updatedLedgerRes = $this->withHeader('Authorization', "Bearer {$token}")
                                  ->getJson("/api/v1/customers/{$customerId}/ledger");

        $updatedLedgerRes->assertStatus(200)
                         ->assertJsonPath('data.total_paid', 4000)
                         ->assertJsonPath('data.outstanding_amount', 1000);

        // 7. Schedule Follow-up Reminder
        $remRes = $this->withHeader('Authorization', "Bearer {$token}")
                       ->postJson('/api/v1/reminders', [
                           'customer_id'   => $customerId,
                           'amount'        => 1000.00,
                           'reminder_date' => now()->addDays(3)->toDateString(),
                           'notes'         => 'Call Arun for remaining ₹1,000',
                       ]);

        $remRes->assertStatus(201)
               ->assertJsonPath('data.reminder.status', 'PENDING');
    }
}
