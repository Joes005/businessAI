<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\Product;
use App\Models\Customer;
use App\Services\AI\BusinessBrainService;
use App\Services\AI\AITaskService;
use App\Services\AI\AIBriefingService;
use App\Services\AI\AIGoalService;

class AIEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_copilot_2_digital_employee_full_suite(): void
    {
        // 1. Setup Business & Owner
        $user = User::create([
            'name'     => 'Merchant Dev 2.0',
            'email'    => 'employee@business.com',
            'password' => bcrypt('password123'),
        ]);

        $business = Business::create([
            'name'     => 'Digital Employee Mart',
            'type'     => 'retail',
            'currency' => 'INR',
        ]);

        $business->users()->attach($user->id, ['role' => 'owner']);
        $user->update(['current_business_id' => $business->id]);

        $token = $user->createToken('auth_token')->plainTextToken;

        // 2. Create Low Stock Product
        Product::create([
            'business_id'         => $business->id,
            'name'                => 'Rice 25kg',
            'purchase_price'      => 1000.00,
            'selling_price'       => 1300.00,
            'stock_quantity'      => 1.00,
            'low_stock_threshold' => 5.00,
        ]);

        // 3. Test Business Brain Model Service
        $brainService = app(BusinessBrainService::class);
        $brainModel = $brainService->buildBrainModel($business->id);
        $this->assertEquals('Digital Employee Mart', $brainModel['business']['name']);
        $this->assertCount(1, $brainModel['operational_risks']);

        // 4. Test Daily Briefing API
        $briefingRes = $this->withHeader('Authorization', "Bearer {$token}")
                            ->getJson('/api/v1/copilot/briefing');

        $briefingRes->assertStatus(200)
                    ->assertJsonPath('success', true)
                    ->assertJsonPath('data.low_stock_count', 1);

        // 5. Test Goals API
        $goalRes = $this->withHeader('Authorization', "Bearer {$token}")
                        ->postJson('/api/v1/copilot/goals', [
                            'title'        => 'Reach ₹1,00,000 Sales',
                            'metric_key'   => 'monthly_sales',
                            'target_value' => 100000.00,
                        ]);

        $goalRes->assertStatus(200)
                ->assertJsonPath('success', true)
                ->assertJsonPath('data.title', 'Reach ₹1,00,000 Sales');

        // 6. Test Multi-step Task Creation API
        $taskRes = $this->withHeader('Authorization', "Bearer {$token}")
                        ->postJson('/api/v1/copilot/tasks', [
                            'goal' => 'Restock low stock products',
                        ]);

        $taskRes->assertStatus(200)
                ->assertJsonPath('success', true)
                ->assertJsonPath('data.status', 'COMPLETED');
    }
}
