<?php

namespace Tests\Feature;

use App\Models\Advertisement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Advertisement API Tests
 * 
 * Tests for the public API endpoints that handle advertisement CRUD operations.
 * These tests ensure data integrity, validation, and proper error handling.
 */
class AdvertisementApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test creating advertisement with valid data
     */
    public function test_can_create_advertisement_with_valid_data(): void
    {
        $data = [
            'title' => 'Test Billboard in Warsaw',
            'type' => 'billboard',
            'city' => 'Warszawa',
            'location' => 'ul. Testowa 1',
            'price' => 1000,
            'price_unit' => 'month',
            'width' => 6,
            'height' => 3,
            'traffic_intensity' => 'high',
            'owner_email' => 'test@example.com',
            'phone' => '123456789',
            'contact_preference' => 'email',
            'offer_type' => 'rent',
            'status' => 'active',
        ];

        $response = $this->postJson('/api/listings', $data, [
            'X-Internal-Key' => config('app.internal_api_key')
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'id',
            'title',
            'type',
            'city',
            'price',
            'created_at'
        ]);

        $this->assertDatabaseHas('advertisements', [
            'title' => 'Test Billboard in Warsaw',
            'type' => 'billboard',
            'price' => 1000,
        ]);
    }

    /**
     * Test creating LED screen with dimensions in meters (frontend sends mm, backend stores m)
     */
    public function test_led_screen_dimensions_are_stored_in_meters(): void
    {
        $data = [
            'title' => 'LED Screen Test',
            'type' => 'led_screen',
            'city' => 'Kraków',
            'location' => 'ul. LED 1',
            'price' => 5000,
            'price_unit' => 'month',
            'width' => 2.5,    // Should be stored as 2.5m
            'height' => 1.5,   // Should be stored as 1.5m
            'owner_email' => 'led@example.com',
            'phone' => '987654321',
            'contact_preference' => 'email',
            'offer_type' => 'rent',
            'status' => 'active',
        ];

        $response = $this->postJson('/api/listings', $data, [
            'X-Internal-Key' => config('app.internal_api_key')
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('advertisements', [
            'title' => 'LED Screen Test',
            'type' => 'led_screen',
            'width' => 2.5,  // Stored in meters
            'height' => 1.5,
        ]);
    }

    /**
     * Test validation: price must be positive
     */
    public function test_cannot_create_advertisement_with_negative_price(): void
    {
        $data = [
            'title' => 'Invalid Ad',
            'type' => 'billboard',
            'city' => 'Warszawa',
            'price' => -100,  // Invalid
            'price_unit' => 'month',
            'owner_email' => 'test@example.com',
        ];

        $response = $this->postJson('/api/listings', $data, [
            'X-Internal-Key' => config('app.internal_api_key')
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['price']);
    }

    /**
     * Test validation: required fields
     */
    public function test_cannot_create_advertisement_without_required_fields(): void
    {
        $data = [
            'title' => 'Incomplete Ad',
            // Missing: type, city, price, owner_email
        ];

        $response = $this->postJson('/api/listings', $data, [
            'X-Internal-Key' => config('app.internal_api_key')
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['type', 'city', 'price', 'owner_email']);
    }

    /**
     * Test validation: invalid advertisement type
     */
    public function test_cannot_create_advertisement_with_invalid_type(): void
    {
        $data = [
            'title' => 'Invalid Type Ad',
            'type' => 'invalid_type',  // Invalid
            'city' => 'Warszawa',
            'price' => 1000,
            'price_unit' => 'month',
            'owner_email' => 'test@example.com',
        ];

        $response = $this->postJson('/api/listings', $data, [
            'X-Internal-Key' => config('app.internal_api_key')
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['type']);
    }

    /**
     * Test validation: invalid price unit
     */
    public function test_cannot_create_advertisement_with_invalid_price_unit(): void
    {
        $data = [
            'title' => 'Invalid Price Unit Ad',
            'type' => 'billboard',
            'city' => 'Warszawa',
            'price' => 1000,
            'price_unit' => 'invalid_unit',  // Invalid
            'owner_email' => 'test@example.com',
        ];

        $response = $this->postJson('/api/listings', $data, [
            'X-Internal-Key' => config('app.internal_api_key')
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['price_unit']);
    }

    /**
     * Test updating advertisement
     */
    public function test_can_update_advertisement(): void
    {
        $ad = Advertisement::factory()->create([
            'title' => 'Original Title',
            'price' => 1000,
        ]);

        $updateData = [
            'title' => 'Updated Title',
            'price' => 1500,
        ];

        $response = $this->putJson("/api/listings/{$ad->id}", $updateData, [
            'X-Internal-Key' => config('app.internal_api_key')
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('advertisements', [
            'id' => $ad->id,
            'title' => 'Updated Title',
            'price' => 1500,
        ]);
    }

    /**
     * Test fetching single advertisement
     */
    public function test_can_fetch_single_advertisement(): void
    {
        $ad = Advertisement::factory()->create([
            'title' => 'Test Billboard',
            'type' => 'billboard',
            'status' => 'active',
        ]);

        $response = $this->getJson("/api/listings/{$ad->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $ad->id,
            'title' => 'Test Billboard',
            'type' => 'billboard',
        ]);
    }

    /**
     * Test fetching all advertisements with filters
     */
    public function test_can_fetch_advertisements_with_type_filter(): void
    {
        Advertisement::factory()->create(['type' => 'billboard', 'status' => 'active']);
        Advertisement::factory()->create(['type' => 'led_screen', 'status' => 'active']);
        Advertisement::factory()->create(['type' => 'billboard', 'status' => 'active']);

        $response = $this->getJson('/api/listings?type=billboard');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }

    /**
     * Test fetching advertisements with city filter
     */
    public function test_can_fetch_advertisements_with_city_filter(): void
    {
        Advertisement::factory()->create(['city' => 'Warszawa', 'status' => 'active']);
        Advertisement::factory()->create(['city' => 'Kraków', 'status' => 'active']);
        Advertisement::factory()->create(['city' => 'Warszawa', 'status' => 'active']);

        $response = $this->getJson('/api/listings?city=Warszawa');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }
}
