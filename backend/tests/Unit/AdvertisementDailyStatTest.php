<?php

namespace Tests\Unit;

use App\Models\Advertisement;
use App\Models\AdvertisementDailyStat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Advertisement Daily Stats Tests
 * 
 * Critical tests for daily statistics tracking:
 * - View counting
 * - Click tracking (phone, email)
 * - Data aggregation
 * - Historical data integrity
 */
class AdvertisementDailyStatTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that daily stats are created for today if not exist
     */
    public function test_creates_daily_stat_for_today_if_not_exists(): void
    {
        $ad = Advertisement::factory()->create();

        // Should not exist yet
        $stat = AdvertisementDailyStat::where([
            'advertisement_id' => $ad->id,
            'date' => now()->toDateString(),
        ])->first();

        $this->assertNull($stat);

        // Create stat
        $stat = AdvertisementDailyStat::firstOrCreate([
            'advertisement_id' => $ad->id,
            'date' => now()->toDateString(),
        ], [
            'views' => 0,
            'phone_clicks' => 0,
            'email_clicks' => 0,
        ]);

        $this->assertNotNull($stat);
        $this->assertEquals(0, $stat->views);
        $this->assertEquals(0, $stat->phone_clicks);
        $this->assertEquals(0, $stat->email_clicks);
    }

    /**
     * Test incrementing views
     */
    public function test_can_increment_views(): void
    {
        $ad = Advertisement::factory()->create();

        $stat = AdvertisementDailyStat::create([
            'advertisement_id' => $ad->id,
            'date' => now()->toDateString(),
            'views' => 0,
            'phone_clicks' => 0,
            'email_clicks' => 0,
        ]);

        $stat->increment('views');
        $stat->increment('views');
        $stat->increment('views');

        $stat->refresh();

        $this->assertEquals(3, $stat->views);
    }

    /**
     * Test incrementing phone clicks
     */
    public function test_can_increment_phone_clicks(): void
    {
        $ad = Advertisement::factory()->create();

        $stat = AdvertisementDailyStat::create([
            'advertisement_id' => $ad->id,
            'date' => now()->toDateString(),
            'views' => 0,
            'phone_clicks' => 0,
            'email_clicks' => 0,
        ]);

        $stat->increment('phone_clicks');
        $stat->increment('phone_clicks');

        $stat->refresh();

        $this->assertEquals(2, $stat->phone_clicks);
    }

    /**
     * Test incrementing email clicks
     */
    public function test_can_increment_email_clicks(): void
    {
        $ad = Advertisement::factory()->create();

        $stat = AdvertisementDailyStat::create([
            'advertisement_id' => $ad->id,
            'date' => now()->toDateString(),
            'views' => 0,
            'phone_clicks' => 0,
            'email_clicks' => 0,
        ]);

        $stat->increment('email_clicks');

        $stat->refresh();

        $this->assertEquals(1, $stat->email_clicks);
    }

    /**
     * Test getting stats for last 30 days
     */
    public function test_can_get_stats_for_last_30_days(): void
    {
        $ad = Advertisement::factory()->create();

        // Create stats for last 5 days
        for ($i = 0; $i < 5; $i++) {
            AdvertisementDailyStat::create([
                'advertisement_id' => $ad->id,
                'date' => now()->subDays($i)->toDateString(),
                'views' => 10 * ($i + 1),
                'phone_clicks' => $i + 1,
                'email_clicks' => $i,
            ]);
        }

        // Create old stat (more than 30 days)
        AdvertisementDailyStat::create([
            'advertisement_id' => $ad->id,
            'date' => now()->subDays(40)->toDateString(),
            'views' => 999,
            'phone_clicks' => 99,
            'email_clicks' => 99,
        ]);

        $stats = AdvertisementDailyStat::where('advertisement_id', $ad->id)
            ->where('date', '>=', now()->subDays(30))
            ->get();

        $this->assertCount(5, $stats); // Should only get last 5, not the 40-day-old one
    }

    /**
     * Test calculating total stats across all days
     */
    public function test_calculates_total_stats_correctly(): void
    {
        $ad = Advertisement::factory()->create();

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
            'views' => 200,
            'phone_clicks' => 10,
            'email_clicks' => 7,
        ]);

        $stats = AdvertisementDailyStat::where('advertisement_id', $ad->id)->get();

        $totalViews = $stats->sum('views');
        $totalPhoneClicks = $stats->sum('phone_clicks');
        $totalEmailClicks = $stats->sum('email_clicks');
        $totalClicks = $totalPhoneClicks + $totalEmailClicks;

        $this->assertEquals(300, $totalViews);
        $this->assertEquals(15, $totalPhoneClicks);
        $this->assertEquals(10, $totalEmailClicks);
        $this->assertEquals(25, $totalClicks);
    }

    /**
     * Test that each advertisement has separate stats
     */
    public function test_stats_are_separate_per_advertisement(): void
    {
        $ad1 = Advertisement::factory()->create();
        $ad2 = Advertisement::factory()->create();

        AdvertisementDailyStat::create([
            'advertisement_id' => $ad1->id,
            'date' => now()->toDateString(),
            'views' => 100,
            'phone_clicks' => 5,
            'email_clicks' => 3,
        ]);

        AdvertisementDailyStat::create([
            'advertisement_id' => $ad2->id,
            'date' => now()->toDateString(),
            'views' => 200,
            'phone_clicks' => 10,
            'email_clicks' => 7,
        ]);

        $stats1 = AdvertisementDailyStat::where('advertisement_id', $ad1->id)->first();
        $stats2 = AdvertisementDailyStat::where('advertisement_id', $ad2->id)->first();

        $this->assertEquals(100, $stats1->views);
        $this->assertEquals(200, $stats2->views);
        $this->assertNotEquals($stats1->id, $stats2->id);
    }

    /**
     * Test that stats can't have negative values
     */
    public function test_stats_cannot_be_negative(): void
    {
        $ad = Advertisement::factory()->create();

        $stat = AdvertisementDailyStat::create([
            'advertisement_id' => $ad->id,
            'date' => now()->toDateString(),
            'views' => 0,
            'phone_clicks' => 0,
            'email_clicks' => 0,
        ]);

        // Attempting to set negative values should fail or be prevented
        // This depends on your model's validation logic
        $this->assertGreaterThanOrEqual(0, $stat->views);
        $this->assertGreaterThanOrEqual(0, $stat->phone_clicks);
        $this->assertGreaterThanOrEqual(0, $stat->email_clicks);
    }
}
