<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\Product;
use App\Models\Customer;

class CopilotAssistantTest extends TestCase
{
    use RefreshDatabase;

    public function test_copilot_conversational_queries_and_proactive_insights_flow(): void
    {
        // 1. Create Business & Owner User
        $user = User::create([
            'name'     => 'Merchant Alex',
            'email'    => 'alex@copilot.com',
            'password' => bcrypt('password123'),
        ]);

        $business = Business::create([
            'name'     => 'Alex Superstore',
            'type'     => 'retail',
            'currency' => 'INR',
        ]);

        $business->users()->attach($user->id, ['role' => 'owner']);
        $user->update(['current_business_id' => $business->id]);

        $token = $user->createToken('auth_token')->plainTextToken;

        // 2. Create Low-Stock Product
        $product = Product::create([
            'business_id'         => $business->id,
            'name'                => 'Organic Honey 500g',
            'sku'                 => 'HON-500',
            'unit'                => 'jar',
            'purchase_price'      => 150.00,
            'selling_price'       => 220.00,
            'stock_quantity'      => 2.00, // Below threshold 5
            'low_stock_threshold' => 5.00,
        ]);

        // 3. Create Customer & Pending Credit Sale Invoice
        $customer = Customer::create([
            'business_id' => $business->id,
            'name'        => 'Vikas Sharma',
            'phone'       => '+919988776655',
        ]);

        $invoiceRes = $this->withHeader('Authorization', "Bearer {$token}")
                           ->postJson('/api/v1/invoices', [
                               'customer_id'    => $customer->id,
                               'customer_name'  => 'Vikas Sharma',
                               'date'           => now()->toDateString(),
                               'items'          => [
                                   [
                                       'product_id' => $product->id,
                                       'quantity'   => 1,
                                       'unit_price' => 220.00,
                                   ],
                               ],
                               'amount_paid'    => 50.00, // Balance due: ₹170
                               'payment_method' => 'CREDIT',
                           ]);

        $invoiceRes->assertStatus(201);

        // 4. Test Copilot Chat API: Today's Sales
        $salesChatRes = $this->withHeader('Authorization', "Bearer {$token}")
                             ->postJson('/api/v1/copilot/chat', [
                                 'prompt' => 'How much did I sell today?',
                             ]);

        $salesChatRes->assertStatus(200)
                     ->assertJsonPath('success', true)
                     ->assertJsonPath('data.intent', 'SALES_TODAY');

        // 5. Test Copilot Chat API: Debtors ("Who owes me money?")
        $debtorsChatRes = $this->withHeader('Authorization', "Bearer {$token}")
                               ->postJson('/api/v1/copilot/chat', [
                                   'prompt' => 'Who owes me money right now?',
                               ]);

        $debtorsChatRes->assertStatus(200)
                       ->assertJsonPath('data.intent', 'DEBTORS')
                       ->assertJsonCount(1, 'data.data');

        // 6. Test Copilot Chat API: Low Stock ("Which items are low in stock?")
        $stockChatRes = $this->withHeader('Authorization', "Bearer {$token}")
                             ->postJson('/api/v1/copilot/chat', [
                                 'prompt' => 'Which items are low in stock?',
                             ]);

        $stockChatRes->assertStatus(200)
                     ->assertJsonPath('data.intent', 'LOW_STOCK')
                     ->assertJsonCount(1, 'data.data');

        // 7. Test Proactive Insights API
        $insightsRes = $this->withHeader('Authorization', "Bearer {$token}")
                            ->getJson('/api/v1/copilot/insights');

        $insightsRes->assertStatus(200)
                   ->assertJsonPath('success', true)
                   ->assertJsonCount(2, 'data.insights');
    }
}
