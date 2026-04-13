<?php

namespace Tests\Feature;

use App\Models\Advertisement;
use App\Models\ManagementToken;
use App\Models\AdvertisementDailyStat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use App\Mail\ManagementLink;
use Tests\TestCase;

/**
 * Management Tests
 *
 * Tests for management token generation, validation, and email sending.
 */
class ManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test sending management link
     */
    public function test_can_send_management_link(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/management/send-link', [
            'email' => 'owner@example.com',
        ], $this->appKeyHeaders());

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Management link has been sent to your email',
        ]);

        // Verify token was created
        $this->assertDatabaseHas('management_tokens', [
            'email' => 'owner@example.com',
        ]);

        // Verify email was sent
        Mail::assertSent(ManagementLink::class, function ($mail) {
            return $mail->hasTo('owner@example.com');
        });
    }

    /**
     * Test sending management link deletes old tokens
     */
    public function test_sending_link_deletes_old_tokens(): void
    {
        Mail::fake();

        // Create old token
        $oldToken = ManagementToken::create([
            'email' => 'owner@example.com',
            'expires_at' => now()->addDays(30),
        ]);

        $this->postJson('/api/management/send-link', [
            'email' => 'owner@example.com',
        ], $this->appKeyHeaders());

        // Old token should be deleted
        $this->assertDatabaseMissing('management_tokens', [
            'id' => $oldToken->id,
        ]);

        // New token should exist
        $this->assertDatabaseHas('management_tokens', [
            'email' => 'owner@example.com',
        ]);

        // Should only have one token
        $this->assertEquals(1, ManagementToken::where('email', 'owner@example.com')->count());
    }

    /**
     * Test sending management link is rate limited
     */
    public function test_sending_management_link_is_rate_limited(): void
    {
        Mail::fake();

        // Make 6 requests (rate limit is 5/hour)
        for ($i = 0; $i < 6; $i++) {
            $response = $this->postJson('/api/management/send-link', [
                'email' => "user{$i}@example.com",
            ], $this->appKeyHeaders());
            
            if ($i < 5) {
                $response->assertStatus(200);
            }
        }

        // 6th request should be rate limited
        $this->assertEquals(429, $response->getStatusCode());
    }

    /**
     * Test validating valid management token
     */
    public function test_can_validate_valid_token(): void
    {
        $token = ManagementToken::create([
            'email' => 'owner@example.com',
            'expires_at' => now()->addDays(30),
        ]);

        // Create advertisements for this email
        Advertisement::factory()->count(3)->create([
            'owner_email' => 'owner@example.com',
        ]);

        $response = $this->getJson("/api/management/validate/{$token->id}", $this->appKeyHeaders());

        $response->assertStatus(200);
        $response->assertJson([
            'valid' => true,
            'email' => 'owner@example.com',
        ]);

        $response->assertJsonStructure([
            'valid',
            'email',
            'expires_at',
            'listings',
        ]);

        $response->assertJsonCount(3, 'listings');
    }

    /**
     * Test validating expired token returns 401
     */
    public function test_validating_expired_token_returns_401(): void
    {
        $token = ManagementToken::create([
            'email' => 'owner@example.com',
            'expires_at' => now()->subDay(), // Expired
        ]);

        $response = $this->getJson("/api/management/validate/{$token->id}", $this->appKeyHeaders());

        $response->assertStatus(401);
        $response->assertJson([
            'valid' => false,
            'message' => 'Invalid or expired token',
        ]);
    }

    /**
     * Test validating non-existent token returns 401
     */
    public function test_validating_non_existent_token_returns_401(): void
    {
        $response = $this->getJson('/api/management/validate/invalid-token-id', $this->appKeyHeaders());

        $response->assertStatus(401);
        $response->assertJson([
            'valid' => false,
        ]);
    }

    /**
     * Test validate token includes 30-day stats
     */
    public function test_validate_token_includes_30day_stats(): void
    {
        $token = ManagementToken::create([
            'email' => 'owner@example.com',
            'expires_at' => now()->addDays(30),
        ]);

        $ad = Advertisement::factory()->create([
            'owner_email' => 'owner@example.com',
        ]);

        // Create stats for last 30 days
        for ($i = 0; $i < 10; $i++) {
            AdvertisementDailyStat::create([
                'advertisement_id' => $ad->id,
                'date' => now()->subDays($i)->format('Y-m-d'),
                'views' => 10,
                'phone_clicks' => 2,
                'email_clicks' => 1,
            ]);
        }

        $response = $this->getJson("/api/management/validate/{$token->id}", $this->appKeyHeaders());

        $response->assertStatus(200);
        
        $listing = $response->json('listings.0');
        $this->assertEquals(100, $listing['views_30d']); // 10 days × 10 views
        $this->assertEquals(20, $listing['phone_clicks_30d']); // 10 days × 2 clicks
        $this->assertEquals(10, $listing['email_clicks_30d']); // 10 days × 1 click
    }

    /**
     * Test validate token only returns ads for that email
     */
    public function test_validate_token_only_returns_own_ads(): void
    {
        $token = ManagementToken::create([
            'email' => 'owner@example.com',
            'expires_at' => now()->addDays(30),
        ]);

        // Create ads for token owner
        Advertisement::factory()->count(2)->create([
            'owner_email' => 'owner@example.com',
        ]);

        // Create ads for different owner
        Advertisement::factory()->count(3)->create([
            'owner_email' => 'other@example.com',
        ]);

        $response = $this->getJson("/api/management/validate/{$token->id}", $this->appKeyHeaders());

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'listings'); // Only owner's ads
    }

    /**
     * Test token expires after 30 days
     */
    public function test_token_expires_after_30_days(): void
    {
        Mail::fake();

        $this->postJson('/api/management/send-link', [
            'email' => 'owner@example.com',
        ], $this->appKeyHeaders());

        $token = ManagementToken::where('email', 'owner@example.com')->first();

        // Token should expire in ~30 days (with some tolerance)
        $expiresIn = now()->diffInDays($token->expires_at);
        $this->assertGreaterThanOrEqual(29, $expiresIn);
        $this->assertLessThanOrEqual(30, $expiresIn);
    }

    /**
     * Test validation requires email
     */
    public function test_sending_link_requires_email(): void
    {
        $response = $this->postJson('/api/management/send-link', [], $this->appKeyHeaders());

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    /**
     * Test validation requires valid email format
     */
    public function test_sending_link_requires_valid_email(): void
    {
        $response = $this->postJson('/api/management/send-link', [
            'email' => 'not-an-email',
        ], $this->appKeyHeaders());

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    /**
     * Test sending link requires app key
     */
    public function test_sending_link_requires_app_key(): void
    {
        // Without app key
        $response = $this->postJson('/api/management/send-link', [
            'email' => 'owner@example.com',
        ]);
        $response->assertStatus(403);

        // With invalid app key
        $response = $this->postJson('/api/management/send-link', [
            'email' => 'owner@example.com',
        ], ['X-App-Key' => 'invalid-key']);
        $response->assertStatus(403);
    }

    /**
     * Test validating token requires app key
     */
    public function test_validating_token_requires_app_key(): void
    {
        $token = ManagementToken::create([
            'email' => 'owner@example.com',
            'expires_at' => now()->addDays(30),
        ]);

        // Without app key
        $response = $this->getJson("/api/management/validate/{$token->id}");
        $response->assertStatus(403);

        // With invalid app key
        $response = $this->getJson("/api/management/validate/{$token->id}", ['X-App-Key' => 'invalid-key']);
        $response->assertStatus(403);
    }
}
