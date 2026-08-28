<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\Product;
use App\Models\Invoice;

class VoiceCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_voice_business_command_processing_in_english_and_tamil(): void
    {
        // 1. Create Business & Owner User
        $user = User::create([
            'name'     => 'Voice User',
            'email'    => 'voice@shop.com',
            'password' => bcrypt('password123'),
        ]);

        $business = Business::create([
            'name'     => 'Voice Mart',
            'type'     => 'retail',
            'currency' => 'INR',
        ]);

        $business->users()->attach($user->id, ['role' => 'owner']);
        $user->update(['current_business_id' => $business->id]);

        $token = $user->createToken('auth_token')->plainTextToken;

        // 2. Test English Voice Command: "Open billing counter"
        $navBillingRes = $this->withHeader('Authorization', "Bearer {$token}")
                              ->postJson('/api/v1/voice/command', [
                                  'transcript' => 'Open billing counter',
                                  'language'   => 'en',
                              ]);

        $navBillingRes->assertStatus(200)
                      ->assertJsonPath('success', true)
                      ->assertJsonPath('data.action_type', 'navigate')
                      ->assertJsonPath('data.target_route', '/billing')
                      ->assertJsonPath('data.spoken_response', 'Opening POS Billing counter now.');

        // 3. Test Tamil Voice Command: "பில்லிங் திற"
        $taBillingRes = $this->withHeader('Authorization', "Bearer {$token}")
                             ->postJson('/api/v1/voice/command', [
                                 'transcript' => 'பில்லிங் திற',
                                 'language'   => 'ta',
                             ]);

        $taBillingRes->assertStatus(200)
                     ->assertJsonPath('data.target_route', '/billing')
                     ->assertJsonPath('data.spoken_response', 'பில்லிங் கவுண்டரை திறக்கிறேன்.');

        // 4. Test Tanglish Voice Command: "yaaru kaasu tharanum"
        $tanglishDebtRes = $this->withHeader('Authorization', "Bearer {$token}")
                                ->postJson('/api/v1/voice/command', [
                                    'transcript' => 'yaaru kaasu tharanum',
                                    'language'   => 'ta',
                                ]);

        $tanglishDebtRes->assertStatus(200)
                        ->assertJsonPath('data.target_route', '/customers');
    }
}
