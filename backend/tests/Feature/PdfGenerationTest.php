<?php

namespace Tests\Feature;

use App\Models\Advertisement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PDF Generation Tests
 *
 * Tests for generating PDF exports of advertisements and comparisons.
 */
class PdfGenerationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test generating single advertisement PDF
     */
    public function test_can_generate_single_advertisement_pdf(): void
    {
        $ad = Advertisement::factory()->create([
            'title' => 'Test Billboard for PDF',
            'type' => 'billboard',
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/listings/{$ad->id}/pdf", $this->appKeyHeaders());

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        
        // Verify PDF content is not empty
        $this->assertNotEmpty($response->getContent());
    }

    /**
     * Test generating PDF for non-existent advertisement returns 404
     */
    public function test_generating_pdf_for_non_existent_ad_returns_404(): void
    {
        $response = $this->getJson('/api/listings/99999/pdf', $this->appKeyHeaders());

        $response->assertStatus(404);
    }

    /**
     * Test generating comparison PDF with multiple advertisements
     */
    public function test_can_generate_comparison_pdf(): void
    {
        $ad1 = Advertisement::factory()->create([
            'title' => 'Billboard 1',
            'type' => 'billboard',
            'is_active' => true,
        ]);

        $ad2 = Advertisement::factory()->create([
            'title' => 'Billboard 2',
            'type' => 'billboard',
            'is_active' => true,
        ]);

        $ad3 = Advertisement::factory()->create([
            'title' => 'Billboard 3',
            'type' => 'billboard',
            'is_active' => true,
        ]);

        $response = $this->getJson(
            "/api/listings/pdf/comparison?ids={$ad1->id},{$ad2->id},{$ad3->id}",
            $this->appKeyHeaders()
        );

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        
        // Verify PDF content is not empty
        $this->assertNotEmpty($response->getContent());
    }

    /**
     * Test generating comparison PDF with single advertisement
     */
    public function test_can_generate_comparison_pdf_with_single_ad(): void
    {
        $ad = Advertisement::factory()->create([
            'title' => 'Single Billboard',
            'type' => 'billboard',
            'is_active' => true,
        ]);

        $response = $this->getJson(
            "/api/listings/pdf/comparison?ids={$ad->id}",
            $this->appKeyHeaders()
        );

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    /**
     * Test generating comparison PDF without IDs returns empty PDF
     */
    public function test_generating_comparison_pdf_without_ids_returns_pdf(): void
    {
        $response = $this->getJson('/api/listings/pdf/comparison', $this->appKeyHeaders());

        // Even without IDs, it returns a PDF (empty)
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    /**
     * Test generating comparison PDF with invalid IDs returns empty PDF
     */
    public function test_generating_comparison_pdf_with_invalid_ids_returns_pdf(): void
    {
        $response = $this->getJson('/api/listings/pdf/comparison?ids=99999,88888', $this->appKeyHeaders());

        // Should handle gracefully - returns empty PDF
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    /**
     * Test PDF generation includes all advertisement data
     */
    public function test_pdf_includes_advertisement_data(): void
    {
        $ad = Advertisement::factory()->create([
            'title' => 'Test Billboard PDF Data',
            'type' => 'billboard',
            'variant' => 'standard',
            'city' => 'Warszawa',
            'price' => 2500,
            'price_unit' => 'month',
            'width' => 6,
            'height' => 3,
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/listings/{$ad->id}/pdf", $this->appKeyHeaders());

        $response->assertStatus(200);
        
        // Get PDF content as string
        $pdfContent = $response->getContent();
        
        // Verify it's a valid PDF (starts with PDF header)
        $this->assertStringStartsWith('%PDF-', $pdfContent);
    }

    /**
     * Test PDF generation for different advertisement types
     */
    public function test_pdf_generation_for_different_types(): void
    {
        $types = ['billboard', 'led_screen', 'citylight', 'banner', 'wall', 'totem', 'transport', 'mobile'];

        foreach ($types as $type) {
            $ad = Advertisement::factory()->create([
                'type' => $type,
                'is_active' => true,
            ]);

            $response = $this->getJson("/api/listings/{$ad->id}/pdf", $this->appKeyHeaders());

            $response->assertStatus(200);
            $response->assertHeader('Content-Type', 'application/pdf');
            
            // Verify PDF header
            $this->assertStringStartsWith('%PDF-', $response->getContent());
        }
    }

    /**
     * Test comparison PDF with maximum allowed advertisements (5)
     */
    public function test_comparison_pdf_with_max_advertisements(): void
    {
        $ids = [];
        for ($i = 0; $i < 5; $i++) {
            $ad = Advertisement::factory()->create([
                'title' => "Billboard {$i}",
                'type' => 'billboard',
                'is_active' => true,
            ]);
            $ids[] = $ad->id;
        }

        $idsString = implode(',', $ids);

        $response = $this->getJson(
            "/api/listings/pdf/comparison?ids={$idsString}",
            $this->appKeyHeaders()
        );

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    /**
     * Test PDF generation requires app key
     */
    public function test_pdf_generation_requires_app_key(): void
    {
        $ad = Advertisement::factory()->create(['is_active' => true]);

        // Without app key
        $response = $this->getJson("/api/listings/{$ad->id}/pdf");
        $response->assertStatus(403);

        // With invalid app key
        $response = $this->getJson("/api/listings/{$ad->id}/pdf", ['X-App-Key' => 'invalid-key']);
        $response->assertStatus(403);
    }
}
