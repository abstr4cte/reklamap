<?php

namespace Tests\Feature;

use App\Models\SearchAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Search Alert Tests
 *
 * Tests for creating and unsubscribing from search alerts.
 */
class SearchAlertTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test creating a search alert
     */
    public function test_can_create_search_alert(): void
    {
        $alertData = [
            'email' => 'user@example.com',
            'type' => 'billboard',
            'city' => 'Warszawa',
            'region' => 'mazowieckie',
            'filters' => [
                'price_from' => 1000,
                'price_to' => 5000,
            ],
        ];

        $response = $this->postJson('/api/search-alerts', $alertData, $this->appKeyHeaders());

        $response->assertStatus(201);
        $response->assertJsonStructure(['message', 'alert']);

        $this->assertDatabaseHas('search_alerts', [
            'email' => 'user@example.com',
            'type' => 'billboard',
            'city' => 'Warszawa',
            'region' => 'mazowieckie',
        ]);

        // Verify unsubscribe token was created
        $alert = SearchAlert::where('email', 'user@example.com')->first();
        $this->assertNotNull($alert->unsubscribe_token);
        $this->assertEquals(40, strlen($alert->unsubscribe_token));
    }

    /**
     * Test creating search alert with minimal data
     */
    public function test_can_create_search_alert_with_minimal_data(): void
    {
        $alertData = [
            'email' => 'minimal@example.com',
        ];

        $response = $this->postJson('/api/search-alerts', $alertData, $this->appKeyHeaders());

        $response->assertStatus(201);

        $this->assertDatabaseHas('search_alerts', [
            'email' => 'minimal@example.com',
            'type' => null,
            'city' => null,
            'region' => null,
        ]);
    }

    /**
     * Test creating duplicate alert returns 200 with message
     */
    public function test_creating_duplicate_alert_returns_message(): void
    {
        $alertData = [
            'email' => 'duplicate@example.com',
            'type' => 'billboard',
            'city' => 'Warszawa',
            'filters' => ['price_from' => 1000],
        ];

        // Create first alert
        $this->postJson('/api/search-alerts', $alertData, $this->appKeyHeaders());

        // Try to create duplicate
        $response = $this->postJson('/api/search-alerts', $alertData, $this->appKeyHeaders());

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Masz już aktywne powiadomienie dla tych kryteriów.'
        ]);

        // Verify only one record exists
        $this->assertEquals(1, SearchAlert::where('email', 'duplicate@example.com')->count());
    }

    /**
     * Test creating alert with different filters is allowed
     */
    public function test_can_create_multiple_alerts_with_different_filters(): void
    {
        $alert1 = [
            'email' => 'multi@example.com',
            'type' => 'billboard',
            'city' => 'Warszawa',
            'filters' => ['price_from' => 1000],
        ];

        $alert2 = [
            'email' => 'multi@example.com',
            'type' => 'billboard',
            'city' => 'Warszawa',
            'filters' => ['price_from' => 2000], // Different filter
        ];

        $this->postJson('/api/search-alerts', $alert1, $this->appKeyHeaders());
        $response = $this->postJson('/api/search-alerts', $alert2, $this->appKeyHeaders());

        $response->assertStatus(201);

        // Verify two records exist
        $this->assertEquals(2, SearchAlert::where('email', 'multi@example.com')->count());
    }

    /**
     * Test validation: email is required
     */
    public function test_email_is_required(): void
    {
        $response = $this->postJson('/api/search-alerts', [], $this->appKeyHeaders());

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    /**
     * Test validation: email must be valid
     */
    public function test_email_must_be_valid(): void
    {
        $response = $this->postJson('/api/search-alerts', [
            'email' => 'not-an-email'
        ], $this->appKeyHeaders());

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    /**
     * Test unsubscribing from search alert
     */
    public function test_can_unsubscribe_from_search_alert(): void
    {
        $alert = SearchAlert::create([
            'email' => 'unsubscribe@example.com',
            'type' => 'billboard',
            'unsubscribe_token' => 'test-token-123456789',
        ]);

        $response = $this->get('/api/search-alerts/unsubscribe/test-token-123456789', $this->appKeyHeaders());

        $response->assertStatus(200);

        $this->assertDatabaseMissing('search_alerts', [
            'id' => $alert->id,
        ]);
    }

    /**
     * Test unsubscribing with invalid token returns 404
     */
    public function test_unsubscribing_with_invalid_token_returns_404(): void
    {
        $response = $this->getJson('/api/search-alerts/unsubscribe/invalid-token', $this->appKeyHeaders());

        $response->assertStatus(404);
    }

    /**
     * Test creating search alert is rate limited
     */
    public function test_creating_search_alert_is_rate_limited(): void
    {
        $alertData = [
            'email' => 'ratelimit@example.com',
            'type' => 'billboard',
        ];

        // Make 11 requests (rate limit is 10/hour)
        for ($i = 0; $i < 11; $i++) {
            $alertData['city'] = "City-{$i}"; // Make each unique
            $response = $this->postJson('/api/search-alerts', $alertData, $this->appKeyHeaders());
            
            if ($i < 10) {
                $this->assertContains($response->getStatusCode(), [200, 201]);
            }
        }

        // 11th request should be rate limited
        $this->assertEquals(429, $response->getStatusCode());
    }

    /**
     * Test search alert stores filters as JSON
     */
    public function test_search_alert_stores_filters_as_json(): void
    {
        $filters = [
            'price_from' => 1000,
            'price_to' => 5000,
            'width_from' => 5,
            'width_to' => 10,
        ];

        $response = $this->postJson('/api/search-alerts', [
            'email' => 'filters@example.com',
            'type' => 'billboard',
            'filters' => $filters,
        ], $this->appKeyHeaders());

        $response->assertStatus(201);

        $alert = SearchAlert::where('email', 'filters@example.com')->first();
        $this->assertEquals($filters, $alert->filters);
    }
}
