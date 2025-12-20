<?php

namespace App\Http\Controllers;

use App\Models\Advertisement;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use App\Mail\ContactAdvertisementOwner;

class AdvertisementController extends Controller
{
    public function index(Request $request)
    {
        $query = Advertisement::where('is_active', 1);
        
        // If ids parameter is provided, filter by those IDs
        if ($request->has('ids')) {
            $ids = explode(',', $request->input('ids'));
            $query->whereIn('id', $ids);
        }
        
        return $query->orderBy('created_at', 'desc')->get();
    }

    public function store(Request $request)
    {
        // Types that require variant field
        $typesWithVariant = ['billboard', 'citylight', 'led_screen', 'banner', 'wall', 'totem', 'transport', 'mobile'];
        $requiresVariant = in_array($request->input('type'), $typesWithVariant);

        $validated = $request->validate([
            'title' => 'required|string',
            'type' => 'required|string',
            'location' => 'required|string',
            'city' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'description' => 'required|string',
            'price' => 'required|numeric',
            'width' => 'nullable|numeric',
            'height' => 'nullable|numeric',
            'owner_email' => 'required|email',
            'price_unit' => 'required|string',
            'region' => 'nullable|string',
            'orientation' => 'required|string',
            'traffic_intensity' => 'nullable|string',
            'offer_type' => 'required|string',
            'phone' => 'nullable|string',
            'contact_preference' => 'nullable|string',
            'has_backlight' => 'nullable|boolean',
            'has_image' => 'nullable|boolean',
            'price_includes_print' => 'nullable|boolean',
            'graphic_design_help' => 'nullable|boolean',
            'has_vat_invoice' => 'nullable|boolean',
            'price_negotiable' => 'nullable|boolean',
            'price_includes_mounting' => 'nullable|boolean',
            'available_from' => 'nullable|date',
            'images' => 'nullable|array',
            'images.*' => 'nullable|string',
            'image_url' => 'nullable|string',
            // Type-specific fields
            'variant' => $requiresVariant ? 'required|string' : 'nullable|string',
            'road_class' => 'nullable|string',
            'traffic_direction' => 'nullable|array',
            'traffic_type' => 'nullable|array',
            'environment' => 'nullable|string',
            'spot_duration' => 'nullable|integer',
            'loop_duration' => 'nullable|integer',
            'transport_scope' => 'nullable|string',
            'vehicle_count' => 'nullable|integer',
            'mobile_exposure_mode' => 'nullable|string',
            'operating_hours' => 'nullable|string',
            'route_area' => 'nullable|string',
            'campaign_duration' => 'nullable|integer',
            'rental_period' => 'nullable|string',
        ]);

        $validated['id'] = (string) Str::uuid();

        // Set defaults if not present (though migration has defaults, explicit is good)
        $validated['status'] = $request->input('status', 'active');
        $validated['is_active'] = $request->input('is_active', true);
        $validated['views'] = 0;

        $ad = Advertisement::create($validated);

        // Clear sitemap cache and notify Google
        $this->notifySearchEngines();

        return response()->json($ad, 201);
    }

    public function show(string $id)
    {
        return Advertisement::findOrFail($id);
    }

    public function update(Request $request, string $id)
    {
        $ad = Advertisement::findOrFail($id);
        $ad->update($request->all());
        
        // Clear sitemap cache and notify Google
        $this->notifySearchEngines();
        
        return $ad;
    }

    public function destroy(string $id)
    {
        $ad = Advertisement::findOrFail($id);
        $ad->delete();
        
        // Clear sitemap cache and notify Google
        $this->notifySearchEngines();
        
        return response()->noContent();
    }

    public function incrementViews(string $id)
    {
        $ad = Advertisement::findOrFail($id);
        $ad->increment('views');
        return response()->json(['message' => 'Views incremented']);
    }

    public function similar(string $id)
    {
        $ad = Advertisement::findOrFail($id);

        $similar = Advertisement::where('city', $ad->city)
            ->where('type', $ad->type)
            ->where('id', '!=', $id)
            ->where('is_active', 1)
            ->limit(4)
            ->get();

        return $similar;
    }

    public function report(Request $request)
    {
        $validated = $request->validate([
            'advertisement_id' => 'required|exists:advertisements,id',
            'reason' => 'required|string',
            'details' => 'required|string',
        ]);

        \App\Models\Report::create($validated);

        return response()->json(['message' => 'Report submitted']);
    }
    public function generatePdf(string $id)
    {
        $ad = Advertisement::findOrFail($id);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.advertisement', ['advertisement' => $ad]);
        return $pdf->download('ogloszenie-' . $ad->id . '.pdf');
    }

    public function generateComparisonPdf(Request $request)
    {
        $ids = explode(',', $request->input('ids'));
        $unit = $request->input('unit', 'month');
        $ads = Advertisement::whereIn('id', $ids)->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.comparison', [
            'advertisements' => $ads,
            'displayUnit' => $unit
        ])->setPaper('a4', 'landscape');

        return $pdf->download('porownanie-ogloszen.pdf');
    }

    public function contactOwner(Request $request, string $id)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'message' => 'required|string|max:5000',
        ]);

        $ad = Advertisement::findOrFail($id);

        // Build advertisement URL
        $advertisementUrl = config('app.frontend_url', config('app.url')) . '/powierzchnia-reklamowa/' . 
            $this->mapTypeToUrlFormat($ad->type) . '/' . 
            \Illuminate\Support\Str::slug($ad->city) . '/' . 
            \Illuminate\Support\Str::slug($ad->title) . '-' . $ad->id;

        try {
            Mail::to($ad->owner_email)->send(
                new ContactAdvertisementOwner(
                    $ad->title,
                    $ad->id,
                    $validated['email'],
                    $validated['message'],
                    $advertisementUrl
                )
            );

            return response()->json([
                'message' => 'Wiadomość została wysłana pomyślnie'
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Error sending contact email: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Wystąpił błąd podczas wysyłania wiadomości. Spróbuj ponownie później.'
            ], 500);
        }
    }

    private function mapTypeToUrlFormat($type)
    {
        $typeMapping = [
            'billboard' => 'billboardy',
            'citylight' => 'citylighty',
            'led_screen' => 'ekrany-led',
            'banner' => 'banery',
            'wall' => 'sciany-reklamowe',
            'totem' => 'totemy-reklamowe',
            'transport' => 'reklama-w-transporcie',
            'mobile' => 'reklama-mobilna',
            'other' => 'inne'
        ];
        
        return $typeMapping[$type] ?? 'inne';
    }

    /**
     * Clear sitemap cache and notify search engines about sitemap update
     */
    private function notifySearchEngines()
    {
        // Clear sitemap cache
        Cache::forget('sitemap_xml');
        
        // Notify Google about sitemap update
        try {
            $sitemapUrl = config('app.url') . '/sitemap.xml';
            Http::timeout(5)->get('https://www.google.com/ping', [
                'sitemap' => $sitemapUrl
            ]);
            
            // Notify Bing
            Http::timeout(5)->get('https://www.bing.com/ping', [
                'sitemap' => $sitemapUrl
            ]);
        } catch (\Exception $e) {
            // Log error but don't fail the request
            \Log::info('Failed to notify search engines: ' . $e->getMessage());
        }
    }
}
