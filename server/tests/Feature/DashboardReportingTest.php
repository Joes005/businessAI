<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\Product;
use App\Models\Invoice;

class DashboardReportingTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_kpis_and_reporting_analytics_flow(): void
    {
        // 1. Create Business & Owner User
        $user = User::create([
            'name'     => 'Store Owner',
            'email'    => 'owner@analytics.com',
            'password' => bcrypt('password123'),
        ]);

        $business = Business::create([
            'name'     => 'Analytics Mart',
            'type'     => 'retail',
            'currency' => 'INR',
        ]);

        $business->users()->attach($user->id, ['role' => 'owner']);
        $user->update(['current_business_id' => $business->id]);

        $token = $user->createToken('auth_token')->plainTextToken;

        // 2. Create Product (Cost: ₹100, Selling: ₹150, Initial Stock: 50)
        $product = Product::create([
            'business_id'         => $business->id,
            'name'                => 'Wireless Headphones',
            'sku'                 => 'HEADPH-01',
            'unit'                => 'pcs',
            'purchase_price'      => 100.00,
            'selling_price'       => 150.00,
            'stock_quantity'      => 50.00,
            'low_stock_threshold' => 5.00,
        ]);

        // 3. Create Invoice (Sell 10 headphones @ ₹150 = ₹1,500, COGS = ₹1,000, Gross Profit = ₹500)
        $invoiceRes = $this->withHeader('Authorization', "Bearer {$token}")
                           ->postJson('/api/v1/invoices', [
                               'customer_name'  => 'Sunil Sharma',
                               'date'           => now()->toDateString(),
                               'items'          => [
                                   [
                                       'product_id' => $product->id,
                                       'quantity'   => 10,
                                       'unit_price' => 150.00,
                                   ],
                               ],
                               'amount_paid'    => 1500.00,
                               'payment_method' => 'CARD',
                           ]);

        $invoiceRes->assertStatus(201);

        // 4. Test Dashboard Analytics API
        $dashRes = $this->withHeader('Authorization', "Bearer {$token}")
                        ->getJson('/api/v1/dashboard');

        $dashRes->assertStatus(200)
                ->assertJsonPath('success', true)
                ->assertJsonPath('data.today_sales', 1500)
                ->assertJsonPath('data.this_month_profit', 500)
                ->assertJsonCount(1, 'data.best_selling_products');

        // 5. Test Sales Summary Report API
        $todayStr = now()->toDateString();
        $salesReportRes = $this->withHeader('Authorization', "Bearer {$token}")
                               ->getJson("/api/v1/reports/sales?start_date={$todayStr}&end_date={$todayStr}");

        $salesReportRes->assertStatus(200)
                       ->assertJsonPath('data.net_revenue', 1500)
                       ->assertJsonPath('data.total_bills', 1);

        // 6. Test Profit & Loss Report API
        $pnlRes = $this->withHeader('Authorization', "Bearer {$token}")
                       ->getJson("/api/v1/reports/profit-loss?start_date={$todayStr}&end_date={$todayStr}");

        $pnlRes->assertStatus(200)
               ->assertJsonPath('data.sales_revenue', 1500)
               ->assertJsonPath('data.cogs', 1000)
               ->assertJsonPath('data.gross_profit', 500)
               ->assertJsonPath('data.margin_percent', 33.33);

        // 7. Test Inventory Valuation Report API (40 items left @ ₹100 cost = ₹4,000 asset value)
        $invValRes = $this->withHeader('Authorization', "Bearer {$token}")
                          ->getJson('/api/v1/reports/inventory');

        $invValRes->assertStatus(200)
                  ->assertJsonPath('data.total_quantity', 40)
                  ->assertJsonPath('data.total_purchase_value', 4000);
    }
}
