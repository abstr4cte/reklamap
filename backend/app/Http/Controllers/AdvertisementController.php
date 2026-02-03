<?php

namespace App\Http\Controllers;

use App\Models\Advertisement;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use App\Mail\ContactAdvertisementOwner;
use App\Mail\AdCreatedConfirmationMail;
use App\Rules\ProfanityRule;
use Spatie\Browsershot\Enums\Polling;

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
            'title' => ['required', 'string', new ProfanityRule],
            'type' => 'required|string',
            'location' => 'required|string',
            'city' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'description' => ['required', 'string', new ProfanityRule],
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
            'transport_scope' => 'nullable|string',
            'vehicle_count' => 'nullable|integer',
            'mobile_exposure_mode' => 'nullable|string',
            'operating_hours' => 'nullable|string',
            'route_area' => 'nullable|string',
            'campaign_duration' => 'nullable|integer',
            'rental_period' => 'nullable|string',
            // LED screen technical fields
            'resolution' => 'nullable|string',
            'pixel_pitch' => 'nullable|numeric|between:0.1,100',
            'brightness' => 'nullable|integer|between:1000,15000',
        ]);


        // Set defaults if not present (though migration has defaults, explicit is good)
        $validated['status'] = $request->input('status', 'active');
        $validated['is_active'] = $request->input('is_active', true);

        $ad = Advertisement::create($validated);

        // Generate slug after the ad is created and has an ID
        $ad->slug = Str::slug($ad->title) . '-' . $ad->id;
        $ad->save();

        // Generate map screenshot
        try {
            $this->generateMapScreenshot($ad);
        } catch (\Exception $e) {
            \Log::error('Error generating map screenshot: ' . $e->getMessage());
        }

        try {
            Mail::to($ad->owner_email)->send(new AdCreatedConfirmationMail($ad));
        } catch (\Exception $e) {
            \Log::error('Error sending ad creation confirmation email: ' . $e->getMessage());
        }

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
        // Types that require variant field
        $typesWithVariant = ['billboard', 'citylight', 'led_screen', 'banner', 'wall', 'totem', 'transport', 'mobile'];
        $requiresVariant = in_array($request->input('type'), $typesWithVariant);

        $validated = $request->validate([
            'title' => ['required', 'string', new ProfanityRule],
            'type' => 'required|string',
            'location' => 'required|string',
            'city' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'description' => ['required', 'string', new ProfanityRule],
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
            'transport_scope' => 'nullable|string',
            'vehicle_count' => 'nullable|integer',
            'mobile_exposure_mode' => 'nullable|string',
            'operating_hours' => 'nullable|string',
            'route_area' => 'nullable|string',
            'campaign_duration' => 'nullable|integer',
            'rental_period' => 'nullable|string',
        ]);

        $ad = Advertisement::findOrFail($id);

        // Check if location changed (latitude or longitude)
        $locationChanged = $ad->latitude != $validated['latitude'] || $ad->longitude != $validated['longitude'];

        // Reset verification status when ad is edited
        $validated['is_verified'] = false;

        $ad->update($validated);

        // Regenerate map screenshot if location changed
        if ($locationChanged) {
            try {
                $this->generateMapScreenshot($ad);
            } catch (\Throwable $e) {
                \Log::error('Error regenerating map screenshot on update: ' . $e->getMessage());
                // Don't fail the update if screenshot generation fails
            }
        }

        // Clear sitemap cache and notify Google
        $this->notifySearchEngines();

        return $ad;
    }

    public function updateStatus(Request $request, string $id)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:active,reserved,soon_available,inactive',
            'available_from' => 'nullable|date|required_if:status,soon_available',
        ]);

        $advertisement = Advertisement::findOrFail($id);
        $advertisement->update($validated);

        return response()->json($advertisement);
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
        Advertisement::findOrFail($id); // Verify ad exists
        
        // Zapisz statystykę dzienną
        $dailyStat = \App\Models\AdvertisementDailyStat::getTodayOrCreate($id);
        $dailyStat->increment('views');
        
        return response()->json(['message' => 'Views incremented']);
    }

    public function incrementPhoneClicks(string $id)
    {
        Advertisement::findOrFail($id); // Verify ad exists
        
        // Zapisz statystykę dzienną
        $dailyStat = \App\Models\AdvertisementDailyStat::getTodayOrCreate($id);
        $dailyStat->increment('phone_clicks');
        
        return response()->json(['message' => 'Phone clicks incremented']);
    }

    public function incrementEmailClicks(string $id)
    {
        Advertisement::findOrFail($id); // Verify ad exists
        
        // Zapisz statystykę dzienną
        $dailyStat = \App\Models\AdvertisementDailyStat::getTodayOrCreate($id);
        $dailyStat->increment('email_clicks');
        
        return response()->json(['message' => 'Email clicks incremented']);
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

    public function submitFeedback(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|in:bug,suggestion,question',
            'email' => 'required|email',
            'message' => 'required|string|max:2000',
            'url' => 'nullable|string',
            'userAgent' => 'nullable|string',
        ]);

        \App\Models\Feedback::create([
            'type' => $validated['type'],
            'email' => $validated['email'],
            'message' => $validated['message'],
            'url' => $validated['url'] ?? null,
            'user_agent' => $validated['userAgent'] ?? null,
        ]);

        return response()->json(['message' => 'Feedback submitted successfully']);
    }

    public function submitContact(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'nullable|string|max:20',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        try {
            // Send email to admin
            $adminEmail = config('mail.from.address', 'admin@reklamap.pl');

            Mail::send('emails.contact', [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'subject' => $validated['subject'],
                'message' => $validated['message'],
            ], function ($message) use ($adminEmail, $validated) {
                $message->to($adminEmail)
                    ->subject('Nowa wiadomość z formularza kontaktowego: ' . $validated['subject'])
                    ->replyTo($validated['email']);
            });

            return response()->json(['message' => 'Wiadomość została wysłana pomyślnie']);
        } catch (\Exception $e) {
            \Log::error('Error sending contact email: ' . $e->getMessage());

            return response()->json([
                'message' => 'Wystąpił błąd podczas wysyłania wiadomości. Spróbuj ponownie później.'
            ], 500);
        }
    }

    public function subscribeNewsletter(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|unique:newsletter_subscribers,email',
        ]);

        try {
            \App\Models\Newsletter::create([
                'email' => $validated['email'],
            ]);

            return response()->json(['message' => 'Dziękujemy za zapisanie się do newslettera!']);
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '23000') {
                // Duplicate entry
                return response()->json([
                    'message' => 'Ten adres e-mail jest już zapisany w naszym newsletterze.'
                ], 409);
            }

            \Log::error('Error subscribing to newsletter: ' . $e->getMessage());

            return response()->json([
                'message' => 'Błąd podczas zapisywania do newslettera. Spróbuj ponownie później.'
            ], 500);
        } catch (\Exception $e) {
            \Log::error('Error subscribing to newsletter: ' . $e->getMessage());

            return response()->json([
                'message' => 'Błąd podczas zapisywania do newslettera. Spróbuj ponownie później.'
            ], 500);
        }
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
     * Generate map screenshot for advertisement
     */
    private function generateMapScreenshot(Advertisement $ad)
    {
        try {
            $screenshotPath = 'maps/' . $ad->id . '-' . time() . '.png';
            $fullPath = storage_path('app/public/' . $screenshotPath);

            // Create directory if it doesn't exist
            if (!is_dir(dirname($fullPath))) {
                mkdir(dirname($fullPath), 0755, true);
            }

            $html = view('map-screenshot', [
                'latitude' => $ad->latitude,
                'longitude' => $ad->longitude,
                'title' => $ad->title,
            ])->render();

            $browsershot = \Spatie\Browsershot\Browsershot::html($html)
                ->windowSize(860, 400)
                ->waitUntilNetworkIdle()
                ->waitForFunction("window.mapReady === true", Polling::RequestAnimationFrame, 10000);
            
            // Only set Node binary if it exists (for local development)
            $nodePath = '/home/dev/.nvm/versions/node/v21.5.0/bin/node';
            if (file_exists($nodePath)) {
                $browsershot->setNodeBinary($nodePath);
            }
            
            $browsershot->save($fullPath);

            // Save path to database
            $ad->map_screenshot_path = $screenshotPath;
            $ad->save();

            \Log::info('Map screenshot generated for ad ' . $ad->id);
        } catch (\Throwable $e) {
            \Log::error('Error generating map screenshot: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update only the active status of an advertisement
     */
    public function updateActiveStatus(Request $request, $id)
    {
        $ad = Advertisement::findOrFail($id);

        $validated = $request->validate([
            'is_active' => 'required|boolean'
        ]);

        $ad->update($validated);

        return response()->json($ad, 200);
    }

    /**
     * Pobierz statystyki dzienne dla ogłoszenia
     */
    public function getDailyStats(string $id)
    {
        $ad = Advertisement::findOrFail($id);
        
        // Pobierz statystyki za ostatnie 30 dni
        $stats = \App\Models\AdvertisementDailyStat::getStatsForPeriod($id, 30);
        
        // Pobierz sumę wszystkich wyświetleń z daily_stats
        $allTimeStats = \App\Models\AdvertisementDailyStat::where('advertisement_id', $id)
            ->selectRaw('SUM(views) as total_views, SUM(phone_clicks) as total_phone_clicks, SUM(email_clicks) as total_email_clicks')
            ->first();
        
        return response()->json([
            'advertisement_id' => $id,
            'title' => $ad->title,
            'city' => $ad->city,
            'type' => $ad->type,
            'stats' => $stats,
            'summary' => [
                'total_views' => $allTimeStats->total_views ?? 0,
                'total_phone_clicks' => $allTimeStats->total_phone_clicks ?? 0,
                'total_email_clicks' => $allTimeStats->total_email_clicks ?? 0,
            ]
        ]);
    }

    /**
     * Pobierz statystyki dzienne dla wielu ogłoszeń
     */
    public function getMultipleDailyStats(Request $request)
    {
        $validated = $request->validate([
            'advertisement_ids' => 'required|array',
            'advertisement_ids.*' => 'required|exists:advertisements,id',
            'days' => 'nullable|integer|min:1|max:365'
        ]);

        $days = $validated['days'] ?? 30;
        $results = [];

        foreach ($validated['advertisement_ids'] as $adId) {
            $ad = Advertisement::find($adId);
            if ($ad) {
                $results[] = [
                    'advertisement_id' => $adId,
                    'title' => $ad->title,
                    'city' => $ad->city,
                    'type' => $ad->type,
                    'stats' => \App\Models\AdvertisementDailyStat::getStatsForPeriod($adId, $days),
                ];
            }
        }

        return response()->json($results);
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
