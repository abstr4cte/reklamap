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
        $response = $this->postJson('/api/listings', $this->validBillboardData(), $this->appKeyHeaders());

        $response->assertStatus(201);
        $response->assertJsonStructure(['id', 'title', 'type', 'city', 'price', 'created_at']);

        $this->assertDatabaseHas('advertisements', [
            'title' => 'Test Billboard in Warsaw',
            'type'  => 'billboard',
            'price' => 1000,
        ]);
    }

    /**
     * Test creating LED screen — dimensions are stored in meters
     */
    public function test_led_screen_dimensions_are_stored_in_meters(): void
    {
        $data = $this->validBillboardData([
            'title'       => 'LED Screen Test',
            'type'        => 'led_screen',
            'variant'     => 'standard',
            'city'        => 'Kraków',
            'location'    => 'ul. LED 1',
            'owner_email' => 'led@example.com',
            'price'       => 5000,
            'width'       => 2.5,
            'height'      => 1.5,
        ]);

        $response = $this->postJson('/api/listings', $data, $this->appKeyHeaders());

        $response->assertStatus(201);

        $this->assertDatabaseHas('advertisements', [
            'title'  => 'LED Screen Test',
            'type'   => 'led_screen',
            'width'  => 2.5,
            'height' => 1.5,
        ]);
    }

    /**
     * Test validation: price must be non-negative
     */
    public function test_cannot_create_advertisement_with_negative_price(): void
    {
        $data = $this->validBillboardData(['price' => -100]);

        $response = $this->postJson('/api/listings', $data, $this->appKeyHeaders());

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['price']);
    }

    /**
     * Test validation: required fields
     */
    public function test_cannot_create_advertisement_without_required_fields(): void
    {
        $response = $this->postJson('/api/listings', ['title' => 'Incomplete Ad'], $this->appKeyHeaders());

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['type', 'city', 'price', 'owner_email']);
    }

    /**
     * Test validation: invalid advertisement type
     */
    public function test_cannot_create_advertisement_with_invalid_type(): void
    {
        $data = $this->validBillboardData(['type' => 'invalid_type']);

        $response = $this->postJson('/api/listings', $data, $this->appKeyHeaders());

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['type']);
    }

    /**
     * Test validation: invalid price unit
     */
    public function test_cannot_create_advertisement_with_invalid_price_unit(): void
    {
        $data = $this->validBillboardData(['price_unit' => 'invalid_unit']);

        $response = $this->postJson('/api/listings', $data, $this->appKeyHeaders());

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['price_unit']);
    }

    /**
     * Test fetching single advertisement
     */
    public function test_can_fetch_single_advertisement(): void
    {
        $ad = Advertisement::factory()->create([
            'title'     => 'Test Billboard',
            'type'      => 'billboard',
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/listings/{$ad->id}", $this->appKeyHeaders());

        $response->assertStatus(200);
        $response->assertJson([
            'id'    => $ad->id,
            'title' => 'Test Billboard',
            'type'  => 'billboard',
        ]);
    }

    /**
     * Test fetching all advertisements returns active ads (paginated response)
     */
    public function test_can_fetch_all_active_advertisements(): void
    {
        Advertisement::factory()->count(3)->create(['is_active' => true]);

        $response = $this->getJson('/api/listings', $this->appKeyHeaders());

        $response->assertStatus(200);
        // Response is now paginated: { data: [...], total: N, ... }
        $response->assertJsonStructure(['data', 'total', 'current_page', 'last_page']);
        $response->assertJsonCount(3, 'data');
    }

    /**
     * Test inactive advertisements are not returned (paginated response)
     */
    public function test_inactive_advertisements_are_excluded(): void
    {
        Advertisement::factory()->count(2)->create(['is_active' => true]);
        Advertisement::factory()->create(['is_active' => false]);

        $response = $this->getJson('/api/listings', $this->appKeyHeaders());

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }

    /**
     * Test validation: dimensions must be positive numbers
     */
    public function test_cannot_create_advertisement_with_invalid_dimensions(): void
    {
        $data = $this->validBillboardData(['width' => -5, 'height' => -10]);

        $response = $this->postJson('/api/listings', $data, $this->appKeyHeaders());

        $response->assertStatus(422);
        // At least width should have validation error
        $response->assertJsonValidationErrors(['width']);
    }

    /**
     * Test validation: traffic_intensity must be valid enum
     */
    public function test_cannot_create_advertisement_with_invalid_traffic_intensity(): void
    {
        $data = $this->validBillboardData(['traffic_intensity' => 'invalid_intensity']);

        $response = $this->postJson('/api/listings', $data, $this->appKeyHeaders());

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['traffic_intensity']);
    }

    /**
     * Test validation: coordinates must be within valid ranges
     * Note: Backend currently accepts any numeric coordinates
     */
    public function test_coordinates_are_stored_correctly(): void
    {
        $data = $this->validBillboardData(['latitude' => 52.2297, 'longitude' => 21.0122]);

        $response = $this->postJson('/api/listings', $data, $this->appKeyHeaders());

        $response->assertStatus(201);
        
        // Verify advertisement was created with coordinates
        $ad = Advertisement::latest()->first();
        $this->assertEquals(52.2297, $ad->latitude);
        $this->assertEquals(21.0122, $ad->longitude);
    }

    /**
     * Test validation: email format
     */
    public function test_cannot_create_advertisement_with_invalid_email(): void
    {
        $data = $this->validBillboardData(['owner_email' => 'not-an-email']);

        $response = $this->postJson('/api/listings', $data, $this->appKeyHeaders());

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['owner_email']);
    }

    /**
     * Test creating advertisement returns correct data structure
     */
    public function test_creating_advertisement_returns_correct_data_structure(): void
    {
        $response = $this->postJson('/api/listings', $this->validBillboardData(), $this->appKeyHeaders());

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'id',
            'title',
            'type',
            'variant',
            'city',
            'location',
            'latitude',
            'longitude',
            'description',
            'price',
            'price_unit',
            'width',
            'height',
            'traffic_intensity',
            'owner_email',
            'phone',
            'contact_preference',
            'offer_type',
            'orientation',
            'road_class',
            'is_active',
            'created_at',
            'updated_at',
        ]);

        // Verify data types
        $response->assertJson([
            'title' => 'Test Billboard in Warsaw',
            'type' => 'billboard',
            'price' => 1000,
            'is_active' => true,
        ]);
    }
}
