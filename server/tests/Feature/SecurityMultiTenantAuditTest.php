<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Invoice;

class SecurityMultiTenantAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_requests_are_blocked(): void
    {
        $unauthDashboardRes = $this->getJson('/api/v1/dashboard');
        $unauthDashboardRes->assertStatus(401);

        $unauthProductsRes = $this->getJson('/api/v1/products');
        $unauthProductsRes->assertStatus(401);
    }

    public function test_multi_tenant_idor_isolation_and_payment_validation(): void
    {
        // 1. Setup Business A & Owner User A
        $userA = User::create([
            'name'     => 'Alice Owner',
            'email'    => 'alice@businessa.com',
            'password' => bcrypt('password123'),
        ]);

        $businessA = Business::create([
            'name'     => 'Business A Mart',
            'type'     => 'retail',
            'currency' => 'INR',
        ]);

        $businessA->users()->attach($userA->id, ['role' => 'owner']);
        $userA->update(['current_business_id' => $businessA->id]);

        $tokenA = $userA->createToken('auth_token')->plainTextToken;

        // 2. Setup Business B & Product/Customer/Invoice B
        $userB = User::create([
            'name'     => 'Bob Owner',
            'email'    => 'bob@businessb.com',
            'password' => bcrypt('password123'),
        ]);

        $businessB = Business::create([
            'name'     => 'Business B Store',
            'type'     => 'retail',
            'currency' => 'USD',
        ]);

        $businessB->users()->attach($userB->id, ['role' => 'owner']);
        $userB->update(['current_business_id' => $businessB->id]);

        $productB = Product::create([
            'business_id'         => $businessB->id,
            'name'                => 'Secret Tech Gadget',
            'purchase_price'      => 100.00,
            'selling_price'       => 200.00,
            'stock_quantity'      => 50.00,
            'low_stock_threshold' => 5.00,
        ]);

        $customerB = Customer::create([
            'business_id' => $businessB->id,
            'name'        => 'VIP Client B',
            'phone'       => '+15551234567',
        ]);

        $invoiceB = Invoice::create([
            'business_id'    => $businessB->id,
            'customer_id'    => $customerB->id,
            'user_id'        => $userB->id,
            'invoice_number' => 'INV-20001',
            'customer_name'  => 'VIP Client B',
            'date'           => now()->toDateString(),
            'subtotal'       => 200.00,
            'grand_total'    => 200.00,
            'amount_paid'    => 0.00,
            'balance_due'    => 200.00,
            'payment_method' => 'CREDIT',
            'payment_status' => 'UNPAID',
        ]);

        // 3. IDOR AUDIT: User A attempts to view Business B's Product -> Must return 404
        $productAccessRes = $this->withHeader('Authorization', "Bearer {$tokenA}")
                                 ->getJson("/api/v1/products/{$productB->id}");
        $productAccessRes->assertStatus(404);

        // 4. IDOR AUDIT: User A attempts to view Business B's Customer -> Must return 404
        $customerAccessRes = $this->withHeader('Authorization', "Bearer {$tokenA}")
                                  ->getJson("/api/v1/customers/{$customerB->id}");
        $customerAccessRes->assertStatus(404);

        // 5. IDOR AUDIT: User A attempts to view Business B's Invoice -> Must return 404
        $invoiceAccessRes = $this->withHeader('Authorization', "Bearer {$tokenA}")
                                 ->getJson("/api/v1/invoices/{$invoiceB->id}");
        $invoiceAccessRes->assertStatus(404);

        // 6. FINANCIAL INTEGRITY AUDIT: Submit non-positive payment amount -> Must return 422
        $invalidPaymentRes = $this->withHeader('Authorization', "Bearer {$tokenA}")
                                  ->postJson('/api/v1/payments', [
                                      'customer_id'    => $customerB->id,
                                      'amount'         => -50.00,
                                      'payment_method' => 'CASH',
                                  ]);
        $invalidPaymentRes->assertStatus(422);
    }
}
