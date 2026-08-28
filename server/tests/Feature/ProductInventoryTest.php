<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;

class ProductInventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_and_inventory_management_flow(): void
    {
        // 1. Create Business & User
        $user = User::create([
            'name'     => 'Merchant Bob',
            'email'    => 'bob@shop.com',
            'password' => bcrypt('password123'),
        ]);

        $business = Business::create([
            'name'     => 'Bob Corner Store',
            'type'     => 'retail',
            'currency' => 'INR',
        ]);

        $business->users()->attach($user->id, ['role' => 'owner']);
        $user->update(['current_business_id' => $business->id]);

        $token = $user->createToken('auth_token')->plainTextToken;

        // 2. Create Category
        $catRes = $this->withHeader('Authorization', "Bearer {$token}")
                       ->postJson('/api/v1/categories', [
                           'name'  => 'Beverages & Soft Drinks',
                           'color' => '#0d9488',
                       ]);

        $catRes->assertStatus(201)
               ->assertJsonPath('success', true);

        $categoryId = $catRes->json('data.category.id');

        // 3. Create Product with initial stock
        $prodRes = $this->withHeader('Authorization', "Bearer {$token}")
                        ->postJson('/api/v1/products', [
                            'name'                => 'Fresh Orange Juice 1L',
                            'category_id'         => $categoryId,
                            'sku'                 => 'BEV-JUICE-01',
                            'barcode'             => '8901000100',
                            'unit'                => 'bottle',
                            'purchase_price'      => 60.00,
                            'selling_price'       => 85.00,
                            'stock_quantity'      => 20.00,
                            'low_stock_threshold' => 5.00,
                        ]);

        $prodRes->assertStatus(201)
                ->assertJsonPath('success', true)
                ->assertJsonPath('data.product.name', 'Fresh Orange Juice 1L')
                ->assertJsonPath('data.product.is_low_stock', false);

        $productId = $prodRes->json('data.product.id');

        // Verify initial stock movement log created
        $this->assertDatabaseHas('stock_movements', [
            'product_id'   => $productId,
            'type'         => 'PURCHASE',
            'quantity'     => 20.00,
            'stock_before' => 0.00,
            'stock_after'  => 20.00,
        ]);

        // 4. Perform Stock Adjustment (Damage -17 bottles to trigger low stock)
        $adjustRes = $this->withHeader('Authorization', "Bearer {$token}")
                          ->postJson('/api/v1/stock/adjust', [
                              'product_id' => $productId,
                              'type'       => 'DAMAGE',
                              'quantity'   => -17.00,
                              'notes'      => 'Spilled during transport',
                          ]);

        $adjustRes->assertStatus(200)
                  ->assertJsonPath('success', true)
                  ->assertJsonPath('data.product.stock_quantity', 3)
                  ->assertJsonPath('data.product.is_low_stock', true);

        // 5. Test Low Stock Filter Query
        $lowStockQueryRes = $this->withHeader('Authorization', "Bearer {$token}")
                                 ->getJson('/api/v1/products?low_stock=true');

        $lowStockQueryRes->assertStatus(200)
                         ->assertJsonPath('data.low_stock_count', 1)
                         ->assertJsonCount(1, 'data.products.data');

        // 6. Test Product Search Query by SKU
        $searchRes = $this->withHeader('Authorization', "Bearer {$token}")
                          ->getJson('/api/v1/products?search=BEV-JUICE-01');

        $searchRes->assertStatus(200)
                  ->assertJsonCount(1, 'data.products.data');
    }
}
