<?php

namespace Tests\Feature;

use App\Models\Advertisement;
use App\Models\ManagementToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Advertisement Authorization Tests
 *
 * Critical security tests ensuring:
 * - X-App-Key is required for all API routes
 * - Update/delete operations also require a valid management token
 * - GET endpoints are accessible with the app key
 */
class AdvertisementAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that creating advertisement requires valid X-App-Key
     */
    public function test_creating_advertisement_requires_app_key(): void
    {
        $data = $this->validBillboardData();

        // Without key → blocked by VerifyAppKey
        $response = $this->postJson('/api/listings', $data);
        $response->assertStatus(403);

        // With invalid key → blocked
        $response = $this->postJson('/api/listings', $data, ['X-App-Key' => 'wrong-key']);
        $response->assertStatus(403);

        // With valid key → passes authorization, creates ad
        $response = $this->postJson('/api/listings', $data, $this->appKeyHeaders());
        $response->assertStatus(201);
    }

    /**
     * Test that updating advertisement requires X-App-Key AND a valid management token
     */
    public function test_updating_advertisement_requires_app_key_and_management_token(): void
    {
        $ad = Advertisement::factory()->create(['owner_email' => 'owner@example.com']);
        $token = ManagementToken::create([
            'email'      => 'owner@example.com',
            'expires_at' => now()->addHour(),
        ]);

        $updateData = $this->validBillboardData([
            'title'       => 'Updated Title',
            'owner_email' => 'owner@example.com',
        ]);

        // Without any key → blocked by VerifyAppKey
        $response = $this->putJson("/api/listings/{$ad->id}", $updateData);
        $response->assertStatus(403);

        // With invalid app key → blocked
        $response = $this->putJson("/api/listings/{$ad->id}", $updateData, ['X-App-Key' => 'wrong-key']);
        $response->assertStatus(403);

        // With valid app key but no management token → blocked by VerifyManagementToken
        $response = $this->putJson("/api/listings/{$ad->id}", $updateData, $this->appKeyHeaders());
        $response->assertStatus(403);

        // With valid app key + valid management token → allowed
        $response = $this->putJson("/api/listings/{$ad->id}", $updateData, array_merge(
            $this->appKeyHeaders(),
            ['X-Management-Token' => $token->id]
        ));
        $response->assertStatus(200);
    }

    /**
     * Test that deleting advertisement requires X-App-Key AND a valid management token
     */
    public function test_deleting_advertisement_requires_app_key_and_management_token(): void
    {
        $ad = Advertisement::factory()->create(['owner_email' => 'owner@example.com']);
        $token = ManagementToken::create([
            'email'      => 'owner@example.com',
            'expires_at' => now()->addHour(),
        ]);

        // Without any key → blocked
        $response = $this->deleteJson("/api/listings/{$ad->id}");
        $response->assertStatus(403);

        // With invalid app key → blocked
        $response = $this->deleteJson("/api/listings/{$ad->id}", [], ['X-App-Key' => 'wrong-key']);
        $response->assertStatus(403);

        // With valid app key but no management token → blocked
        $response = $this->deleteJson("/api/listings/{$ad->id}", [], $this->appKeyHeaders());
        $response->assertStatus(403);

        // With valid app key + valid management token → allowed
        $response = $this->deleteJson("/api/listings/{$ad->id}", [], array_merge(
            $this->appKeyHeaders(),
            ['X-Management-Token' => $token->id]
        ));
        $response->assertStatus(204);
    }

    /**
     * Test that GET listings endpoints require X-App-Key
     */
    public function test_listing_endpoints_require_app_key(): void
    {
        $ad = Advertisement::factory()->create(['is_active' => true]);

        // GET /api/listings without key → blocked
        $this->getJson('/api/listings')->assertStatus(403);

        // GET /api/listings/{id} without key → blocked
        $this->getJson("/api/listings/{$ad->id}")->assertStatus(403);

        // With valid app key → allowed
        $this->getJson('/api/listings', $this->appKeyHeaders())->assertStatus(200);
        $this->getJson("/api/listings/{$ad->id}", $this->appKeyHeaders())->assertStatus(200);
    }

    /**
     * Test that increment-views requires X-App-Key
     */
    public function test_increment_views_requires_app_key(): void
    {
        $ad = Advertisement::factory()->create();

        // Without key → blocked
        $this->postJson("/api/listings/{$ad->id}/increment-views")->assertStatus(403);

        // With valid key → allowed
        $this->postJson("/api/listings/{$ad->id}/increment-views", [], $this->appKeyHeaders())->assertStatus(200);
    }
}
