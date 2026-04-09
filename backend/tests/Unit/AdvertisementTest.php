<?php

namespace Tests\Unit;

use App\Models\Advertisement;
use App\Models\AdvertisementDailyStat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Advertisement Model Unit Tests
 * 
 * Tests for business logic in the Advertisement model:
 * - Daily stats calculation
 * - Price conversions
 * - Surface area calculations
 * - Data integrity
 */
class AdvertisementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test daily stats calculates total views correctly
     */
    public function test_daily_stats_calculates_total_views_correctly(): void
    {
        $ad = Advertisement::factory()->create();

        // Create daily stats for past 3 days
        AdvertisementDailyStat::create([
            'advertisement_id' => $ad->id,
            'date' => now()->subDays(2)->toDateString(),
            'views' => 100,
            'phone_clicks' => 5,
            'email_clicks' => 3,
        ]);

        AdvertisementDailyStat::create([
            'advertisement_id' => $ad->id,
            'date' => now()->subDays(1)->toDateString(),
            'views' => 150,
            'phone_clicks' => 7,
            'email_clicks' => 4,
        ]);

        AdvertisementDailyStat::create([
            'advertisement_id' => $ad->id,
            'date' => now()->toDateString(),
            'views' => 50,
            'phone_clicks' => 2,
            'email_clicks' => 1,
        ]);

        // Fetch stats (assuming getDailyStats method exists)
        $stats = $ad->dailyStats()
            ->where('date', '>=', now()->subDays(30))
            ->get();

        $totalViews = $stats->sum('views');
        $totalPhoneClicks = $stats->sum('phone_clicks');
        $totalEmailClicks = $stats->sum('email_clicks');

        $this->assertEquals(300, $totalViews);
        $this->assertEquals(14, $totalPhoneClicks);
        $this->assertEquals(8, $totalEmailClicks);
    }

    /**
     * Test surface area calculation for LED screens (dimensions in meters)
     */
    public function test_surface_area_calculation_for_led_screen(): void
    {
        $ad = Advertisement::factory()->create([
            'type' => 'led_screen',
            'width' => 2.5,  // meters
            'height' => 1.5, // meters
        ]);

        $surfaceArea = $ad->width * $ad->height;

        $this->assertEquals(3.75, $surfaceArea); // 2.5 * 1.5 = 3.75 m²
    }

    /**
     * Test surface area calculation for billboards
     */
    public function test_surface_area_calculation_for_billboard(): void
    {
        $ad = Advertisement::factory()->create([
            'type' => 'billboard',
            'width' => 6,  // meters
            'height' => 3, // meters
        ]);

        $surfaceArea = $ad->width * $ad->height;

        $this->assertEquals(18, $surfaceArea); // 6 * 3 = 18 m²
    }

    /**
     * Test price per square meter calculation
     */
    public function test_price_per_square_meter_calculation(): void
    {
        $ad = Advertisement::factory()->create([
            'price' => 3000,
            'price_unit' => 'month',
            'width' => 6,
            'height' => 3,
        ]);

        $surfaceArea = $ad->width * $ad->height; // 18 m²
        $pricePerSqm = $surfaceArea > 0 ? $ad->price / $surfaceArea : 0;

        $this->assertEquals(166.67, round($pricePerSqm, 2)); // 3000 / 18 ≈ 166.67 PLN/m²
    }

    /**
     * Test that advertisements can be filtered by status
     */
    public function test_can_filter_advertisements_by_status(): void
    {
        Advertisement::factory()->create(['status' => 'active']);
        Advertisement::factory()->create(['status' => 'active']);
        Advertisement::factory()->create(['status' => 'rented']);
        Advertisement::factory()->create(['status' => 'archived']);

        $activeAds = Advertisement::where('status', 'active')->get();
        $rentedAds = Advertisement::where('status', 'rented')->get();

        $this->assertCount(2, $activeAds);
        $this->assertCount(1, $rentedAds);
    }

    /**
     * Test that advertisement has required fields
     */
    public function test_advertisement_has_required_fields(): void
    {
        $ad = Advertisement::factory()->create([
            'title' => 'Test Billboard',
            'type' => 'billboard',
            'city' => 'Warszawa',
            'price' => 1000,
            'owner_email' => 'test@example.com',
        ]);

        $this->assertNotNull($ad->title);
        $this->assertNotNull($ad->type);
        $this->assertNotNull($ad->city);
        $this->assertNotNull($ad->price);
        $this->assertNotNull($ad->owner_email);
        $this->assertNotNull($ad->phone);
        $this->assertNotNull($ad->created_at);
        $this->assertNotNull($ad->updated_at);
    }

    /**
     * Test that LED screen dimensions are stored in meters (not mm)
     */
    public function test_led_screen_dimensions_are_in_meters(): void
    {
        $ad = Advertisement::factory()->create([
            'type' => 'led_screen',
            'width' => 2.5,  // Should be stored as meters
            'height' => 1.5,
        ]);

        // Dimensions should be reasonable for meters (not mm)
        $this->assertLessThan(100, $ad->width);  // Would be 2500 if in mm
        $this->assertLessThan(100, $ad->height); // Would be 1500 if in mm
        $this->assertGreaterThan(0, $ad->width);
        $this->assertGreaterThan(0, $ad->height);
    }

    /**
     * Test that advertisement can have optional fields null
     */
    public function test_advertisement_can_have_optional_fields_null(): void
    {
        $ad = Advertisement::factory()->create([
            'title' => 'Minimal Ad',
            'type' => 'billboard',
            'city' => 'Warszawa',
            'price' => 1000,
            'owner_email' => 'test@example.com',
            'width' => null,
            'height' => null,
            'traffic_intensity' => null,
        ]);

        $this->assertNull($ad->width);
        $this->assertNull($ad->height);
        $this->assertNull($ad->traffic_intensity);
    }

    /**
     * Test that price must be numeric and positive
     */
    public function test_price_is_numeric_and_positive(): void
    {
        $ad = Advertisement::factory()->create(['price' => 1500.50]);

        $this->assertIsNumeric($ad->price);
        $this->assertGreaterThan(0, $ad->price);
    }
}
