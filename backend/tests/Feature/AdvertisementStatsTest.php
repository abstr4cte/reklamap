<?php

namespace Tests\Feature;

use App\Models\Advertisement;
use App\Models\AdvertisementDailyStat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Advertisement Statistics Tests
 *
 * Tests for views and clicks tracking using advertisement_daily_stats table.
 */
class AdvertisementStatsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test incrementing views creates daily stat record
     */
    public function test_incrementing_views_creates_daily_stat(): void
    {
        $ad = Advertisement::factory()->create();

        $response = $this->postJson("/api/listings/{$ad->id}/increment-views", [], $this->appKeyHeaders());

        $response->assertStatus(200);

        // Check that a daily stat record was created (date is stored as datetime)
        $stat = AdvertisementDailyStat::where('advertisement_id', $ad->id)
            ->whereDate('date', now())
            ->first();

        $this->assertNotNull($stat);
        $this->assertEquals(1, $stat->views);
    }

    /**
     * Test incrementing views is rate-limited by IP
     */
    public function test_incrementing_views_is_rate_limited(): void
    {
        $ad = Advertisement::factory()->create();

        // First increment should succeed
        $response1 = $this->postJson("/api/listings/{$ad->id}/increment-views", [], $this->appKeyHeaders());
        $response1->assertStatus(200);

        // Second increment from same IP should be rate-limited
        $response2 = $this->postJson("/api/listings/{$ad->id}/increment-views", [], $this->appKeyHeaders());
        $response2->assertStatus(200);
        
        // Verify only 1 view was counted due to rate limiting
        $stat = AdvertisementDailyStat::where('advertisement_id', $ad->id)
            ->whereDate('date', now())
            ->first();

        $this->assertNotNull($stat);
        $this->assertEquals(1, $stat->views);
    }

    /**
     * Test incrementing phone clicks
     */
    public function test_incrementing_phone_clicks(): void
    {
        $ad = Advertisement::factory()->create();

        $response = $this->postJson("/api/listings/{$ad->id}/increment-phone-clicks", [], $this->appKeyHeaders());

        $response->assertStatus(200);

        $stat = AdvertisementDailyStat::where('advertisement_id', $ad->id)
            ->whereDate('date', now())
            ->first();

        $this->assertNotNull($stat);
        $this->assertEquals(1, $stat->phone_clicks);
    }

    /**
     * Test incrementing email clicks
     */
    public function test_incrementing_email_clicks(): void
    {
        $ad = Advertisement::factory()->create();

        $response = $this->postJson("/api/listings/{$ad->id}/increment-email-clicks", [], $this->appKeyHeaders());

        $response->assertStatus(200);

        $stat = AdvertisementDailyStat::where('advertisement_id', $ad->id)
            ->whereDate('date', now())
            ->first();

        $this->assertNotNull($stat);
        $this->assertEquals(1, $stat->email_clicks);
    }

    /**
     * Test getDailyStats returns correct summary
     */
    public function test_get_daily_stats_returns_summary(): void
    {
        $ad = Advertisement::factory()->create();

        // Create stats for last 5 days
        for ($i = 0; $i < 5; $i++) {
            AdvertisementDailyStat::create([
                'advertisement_id' => $ad->id,
                'date' => now()->subDays($i)->format('Y-m-d'),
                'views' => 10,
                'phone_clicks' => 2,
                'email_clicks' => 1,
            ]);
        }

        $response = $this->getJson("/api/listings/{$ad->id}/daily-stats", $this->appKeyHeaders());

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'advertisement_id',
            'title',
            'city',
            'type',
            'stats' => [
                '*' => ['date', 'views', 'phone_clicks', 'email_clicks', 'total_clicks']
            ],
            'summary' => ['total_views', 'total_phone_clicks', 'total_email_clicks']
        ]);

        // Verify summary totals (5 days × values)
        $this->assertEquals(50, $response->json('summary.total_views'));
        $this->assertEquals(10, $response->json('summary.total_phone_clicks'));
        $this->assertEquals(5, $response->json('summary.total_email_clicks'));
    }

    /**
     * Test getMultipleDailyStats returns stats for multiple ads
     */
    public function test_get_multiple_daily_stats(): void
    {
        $ad1 = Advertisement::factory()->create(['is_active' => true]);
        $ad2 = Advertisement::factory()->create(['is_active' => true]);

        // Create stats for both ads
        AdvertisementDailyStat::create([
            'advertisement_id' => $ad1->id,
            'date' => now()->format('Y-m-d'),
            'views' => 100,
            'phone_clicks' => 5,
            'email_clicks' => 3,
        ]);

        AdvertisementDailyStat::create([
            'advertisement_id' => $ad2->id,
            'date' => now()->format('Y-m-d'),
            'views' => 50,
            'phone_clicks' => 2,
            'email_clicks' => 1,
        ]);

        $response = $this->postJson('/api/listings/daily-stats/multiple', [
            'advertisement_ids' => [$ad1->id, $ad2->id]
        ], $this->appKeyHeaders());

        $response->assertStatus(200);
        $response->assertJsonCount(2);

        // Verify first ad structure
        $this->assertEquals($ad1->id, $response->json('0.advertisement_id'));
        $this->assertNotNull($response->json('0.stats'));
        $this->assertIsArray($response->json('0.stats'));
        
        // Verify second ad structure
        $this->assertEquals($ad2->id, $response->json('1.advertisement_id'));
        $this->assertNotNull($response->json('1.stats'));
        $this->assertIsArray($response->json('1.stats'));
    }

    /**
     * Test daily stats aggregation over time period
     */
    public function test_daily_stats_aggregation_over_period(): void
    {
        $ad = Advertisement::factory()->create();

        // Create stats for different time periods
        AdvertisementDailyStat::create([
            'advertisement_id' => $ad->id,
            'date' => now()->subDays(40)->format('Y-m-d'), // Outside 30-day window
            'views' => 1000,
            'phone_clicks' => 50,
            'email_clicks' => 25,
        ]);

        AdvertisementDailyStat::create([
            'advertisement_id' => $ad->id,
            'date' => now()->subDays(15)->format('Y-m-d'), // Inside 30-day window
            'views' => 100,
            'phone_clicks' => 5,
            'email_clicks' => 3,
        ]);

        AdvertisementDailyStat::create([
            'advertisement_id' => $ad->id,
            'date' => now()->format('Y-m-d'), // Today
            'views' => 10,
            'phone_clicks' => 1,
            'email_clicks' => 1,
        ]);

        $response = $this->getJson("/api/listings/{$ad->id}/daily-stats", $this->appKeyHeaders());

        $response->assertStatus(200);

        // Should return last 30 days of stats (excluding 40-day old record)
        $stats = $response->json('stats');
        $this->assertCount(2, $stats); // Only 2 records within 30 days

        // Summary includes ALL TIME stats (not just 30 days)
        $this->assertEquals(1110, $response->json('summary.total_views')); // 1000 + 100 + 10
    }

    /**
     * Test stats for non-existent advertisement returns 404
     */
    public function test_stats_for_non_existent_ad_returns_404(): void
    {
        $response = $this->getJson('/api/listings/99999/daily-stats', $this->appKeyHeaders());

        $response->assertStatus(404);
    }

    /**
     * Test incrementing stats for non-existent advertisement returns 404
     */
    public function test_incrementing_stats_for_non_existent_ad_returns_404(): void
    {
        $response = $this->postJson('/api/listings/99999/increment-views', [], $this->appKeyHeaders());

        $response->assertStatus(404);
    }
}
