<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Invoice;

class WhatsAppAutomationTest extends TestCase
{
    use RefreshDatabase;

    public function test_whatsapp_link_generation_and_automation_audit_flow(): void
    {
        // 1. Create Business & Owner User
        $user = User::create([
            'name'     => 'WhatsApp User',
            'email'    => 'whatsapp@shop.com',
            'password' => bcrypt('password123'),
        ]);

        $business = Business::create([
            'name'     => 'WhatsApp Mart',
            'type'     => 'retail',
            'currency' => 'INR',
        ]);

        $business->users()->attach($user->id, ['role' => 'owner']);
        $user->update(['current_business_id' => $business->id]);

        $token = $user->createToken('auth_token')->plainTextToken;

        // 2. Create Low-Stock Product & Customer
        $product = Product::create([
            'business_id'         => $business->id,
            'name'                => 'Basmati Rice 10kg',
            'purchase_price'      => 400.00,
            'selling_price'       => 550.00,
            'stock_quantity'      => 2.00,
            'low_stock_threshold' => 5.00,
        ]);

        $customer = Customer::create([
            'business_id' => $business->id,
            'name'        => 'Rajesh Khanna',
            'phone'       => '+919876543210',
        ]);

        // 3. Create Invoice
        $invoice = Invoice::create([
            'business_id'    => $business->id,
            'customer_id'    => $customer->id,
            'user_id'        => $user->id,
            'invoice_number' => 'INV-10001',
            'customer_name'  => 'Rajesh Khanna',
            'customer_phone' => '+919876543210',
            'date'           => now()->toDateString(),
            'subtotal'       => 550.00,
            'grand_total'    => 550.00,
            'amount_paid'    => 200.00, // Balance due: 350
            'balance_due'    => 350.00,
            'payment_method' => 'CREDIT',
            'payment_status' => 'PARTIAL',
        ]);

        // 4. Test WhatsApp Invoice Link API
        $invLinkRes = $this->withHeader('Authorization', "Bearer {$token}")
                           ->postJson("/api/v1/whatsapp/invoice-link/{$invoice->id}");

        $invLinkRes->assertStatus(200)
                   ->assertJsonPath('success', true);

        $url = $invLinkRes->json('data.whatsapp_url');
        $this->assertStringContainsString('https://wa.me/919876543210?text=', $url);

        // 5. Test WhatsApp Debt Reminder Link API
        $remLinkRes = $this->withHeader('Authorization', "Bearer {$token}")
                           ->postJson('/api/v1/whatsapp/reminder-link', [
                               'customer_id' => $customer->id,
                           ]);

        $remLinkRes->assertStatus(200)
                   ->assertJsonPath('success', true);

        $remUrl = $remLinkRes->json('data.whatsapp_url');
        $this->assertStringContainsString('https://wa.me/919876543210?text=', $remUrl);

        // 6. Test Update WhatsApp Template API
        $tplRes = $this->withHeader('Authorization', "Bearer {$token}")
                       ->putJson('/api/v1/whatsapp/templates', [
                           'type'          => 'INVOICE',
                           'template_text' => 'Hello {customer_name}, invoice {invoice_number} total is {grand_total}. Thanks!',
                       ]);

        $tplRes->assertStatus(200)
               ->assertJsonPath('success', true);

        // 7. Test Automation Trigger Audit API
        $autoRes = $this->withHeader('Authorization', "Bearer {$token}")
                        ->postJson('/api/v1/automation/run');

        $autoRes->assertStatus(200)
                ->assertJsonPath('success', true)
                ->assertJsonPath('data.logs_count', 2); // 1 low stock + 1 debtor
    }
}
