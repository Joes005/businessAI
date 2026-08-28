<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Business;

class AuthBusinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_registration_and_business_creation_flow(): void
    {
        $this->withoutExceptionHandling();
        // 1. Register User
        $registerRes = $this->postJson('/api/v1/auth/register', [
            'name'     => 'Sarah Connor',
            'email'    => 'sarah@cyber.com',
            'password' => 'password123',
            'phone'    => '+919999988888',
        ]);

        $registerRes->assertStatus(201)
                    ->assertJsonPath('success', true);

        $token = $registerRes->json('data.token');
        $this->assertNotEmpty($token);

        // 2. Login User
        $loginRes = $this->postJson('/api/v1/auth/login', [
            'email'    => 'sarah@cyber.com',
            'password' => 'password123',
        ]);

        $loginRes->assertStatus(200)
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('data.has_business', false);

        $token = $loginRes->json('data.token');

        // 3. Create Business
        $businessRes = $this->withHeader('Authorization', "Bearer {$token}")
                            ->postJson('/api/v1/businesses', [
                                'name'     => 'Techtronics Retail',
                                'type'     => 'retail',
                                'category' => 'Electronics & Mobiles',
                                'currency' => 'INR',
                                'phone'    => '+919999988888',
                            ]);

        $businessRes->assertStatus(201)
                    ->assertJsonPath('success', true)
                    ->assertJsonPath('data.business.name', 'Techtronics Retail');

        // 4. Verify Authenticated Profile (me)
        $meRes = $this->withHeader('Authorization', "Bearer {$token}")
                      ->getJson('/api/v1/auth/me');

        $meRes->assertStatus(200)
              ->assertJsonPath('success', true)
              ->assertJsonPath('data.has_business', true)
              ->assertJsonPath('data.current_business.name', 'Techtronics Retail');
    }
}
