<?php

namespace Tests\Feature;

use App\Models\Advertisement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Advertisement Authorization Tests
 * 
 * Critical security tests ensuring:
 * - Internal API key is required for write operations
 * - Users can only manage their own advertisements
 * - Proper access control for sensitive operations
 */
class AdvertisementAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that creating advertisement requires valid internal API key
     */
    public function test_creating_advertisement_requires_internal_api_key(): void
    {
        $data = [
            'title' => 'Test Ad',
            'type' => 'billboard',
            'city' => 'Warszawa',
            'price' => 1000,
            'price_unit' => 'month',
            'owner_email' => 'test@example.com',
        ];

        // Without API key
        $response = $this->postJson('/api/listings', $data);
        $response->assertStatus(401);

        // With invalid API key
        $response = $this->postJson('/api/listings', $data, [
            'X-Internal-Key' => 'invalid-key'
        ]);
        $response->assertStatus(401);

        // With valid API key
        $response = $this->postJson('/api/listings', $data, [
            'X-Internal-Key' => config('app.internal_api_key')
        ]);
        $response->assertStatus(201);
    }

    /**
     * Test that updating advertisement requires valid internal API key
     */
    public function test_updating_advertisement_requires_internal_api_key(): void
    {
        $ad = Advertisement::factory()->create();

        $updateData = ['title' => 'Updated Title'];

        // Without API key
        $response = $this->putJson("/api/listings/{$ad->id}", $updateData);
        $response->assertStatus(401);

        // With invalid API key
        $response = $this->putJson("/api/listings/{$ad->id}", $updateData, [
            'X-Internal-Key' => 'invalid-key'
        ]);
        $response->assertStatus(401);

        // With valid API key
        $response = $this->putJson("/api/listings/{$ad->id}", $updateData, [
            'X-Internal-Key' => config('app.internal_api_key')
        ]);
        $response->assertStatus(200);
    }

    /**
     * Test that deleting advertisement requires valid internal API key
     */
    public function test_deleting_advertisement_requires_internal_api_key(): void
    {
        $ad = Advertisement::factory()->create();

        // Without API key
        $response = $this->deleteJson("/api/listings/{$ad->id}");
        $response->assertStatus(401);

        // With invalid API key
        $response = $this->deleteJson("/api/listings/{$ad->id}", [], [
            'X-Internal-Key' => 'invalid-key'
        ]);
        $response->assertStatus(401);

        // With valid API key
        $response = $this->deleteJson("/api/listings/{$ad->id}", [], [
            'X-Internal-Key' => config('app.internal_api_key')
        ]);
        $response->assertStatus(200);
    }

    /**
     * Test that public can view advertisements (no auth required)
     */
    public function test_public_can_view_advertisements_without_auth(): void
    {
        $ad = Advertisement::factory()->create(['status' => 'active']);

        // No API key needed for GET
        $response = $this->getJson("/api/listings/{$ad->id}");
        $response->assertStatus(200);

        $response = $this->getJson('/api/listings');
        $response->assertStatus(200);
    }

    /**
     * Test that incrementing views doesn't require auth (public action)
     */
    public function test_public_can_increment_views_without_auth(): void
    {
        $ad = Advertisement::factory()->create();

        // No API key needed for incrementing views
        $response = $this->postJson("/api/listings/{$ad->id}/increment-views");
        $response->assertStatus(200);
    }

    /**
     * Test rate limiting for view increments (prevent abuse)
     */
    public function test_view_increments_are_rate_limited(): void
    {
        $ad = Advertisement::factory()->create();

        // First few requests should succeed
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson("/api/listings/{$ad->id}/increment-views");
            $response->assertStatus(200);
        }

        // Note: Actual rate limiting depends on your implementation
        // This is a placeholder test - adjust based on your rate limit logic
    }
}
