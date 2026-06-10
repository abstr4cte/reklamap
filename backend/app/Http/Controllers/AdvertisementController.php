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
use App\Mail\NewAdvertisementNotificationMail;
use App\Mail\FeedbackConfirmationMail;
use App\Mail\AdminFeedbackNotificationMail;
use App\Mail\AdminReportNotificationMail;
use App\Rules\ProfanityRule;
use App\Services\SearchAlertService;
use App\Models\Newsletter;



class AdvertisementController extends Controller
{
    public function index(Request $request)
    {
        // Special case: return specific ads by IDs (used by comparison page)
        if ($request->has('ids')) {
            $ids = explode(',', $request->input('ids'));
            $ads = Advertisement::where('is_active', 1)->whereIn('id', $ids)->get();
            $adsOrdered = collect();
            foreach ($ids as $id) {
                $ad = $ads->firstWhere('id', $id);
                if ($ad) $adsOrdered->push($ad);
            }
            return $adsOrdered;
        }

        $query = $this->buildFilteredQuery($request);

        // --- Sorting ---
        $sort = $request->input('sort', 'default');
        $pricePerDaySql = "
            CASE
                WHEN price_unit = 'day'      THEN price
                WHEN price_unit = 'week'     THEN price / 7.0
                WHEN price_unit = 'month'    THEN price / 30.0
                WHEN price_unit = 'year'     THEN price / 365.0
                WHEN price_unit = 'campaign' THEN price / COALESCE(NULLIF(campaign_duration, 0), 30.0)
                ELSE price / 30.0
            END
        ";

        switch ($sort) {
            case 'newest': // jawny wybór z dropdowna — czysta data, bez dywersyfikacji
                $query->orderBy('created_at', 'desc')->orderBy('id', 'desc'); break;
            case 'oldest':
                $query->orderBy('created_at', 'asc'); break;
            case 'name-asc':
                $query->orderBy('title', 'asc'); break;
            case 'name-desc':
                $query->orderBy('title', 'desc'); break;
            case 'price-day-asc':
            case 'price-week-asc':
            case 'price-month-asc':
            case 'price-year-asc':
            case 'price-campaign-asc':
                $query->orderByRaw("($pricePerDaySql) ASC"); break;
            case 'price-day-desc':
            case 'price-week-desc':
            case 'price-month-desc':
            case 'price-year-desc':
            case 'price-campaign-desc':
                $query->orderByRaw("($pricePerDaySql) DESC"); break;
            case 'price-sqm-asc':
                $query->orderByRaw("CASE WHEN width > 0 AND height > 0 THEN ($pricePerDaySql) / (width * height) ELSE 99999999 END ASC"); break;
            case 'price-sqm-desc':
                $query->orderByRaw("CASE WHEN width > 0 AND height > 0 THEN ($pricePerDaySql) / (width * height) ELSE 0 END DESC"); break;
            default: // newest — z dywersyfikacją per operator (anti-flood, B-6).
                // ROW_NUMBER per owner_email przeplata wystawców: pierwszy ekran =
                // po jednym (najnowszym) nośniku każdego operatora, potem drugi rząd
                // itd. Pojedynczy operator z dużym portfolio (np. import 192 nośników)
                // nie zalewa pierwszych stron. Wewnątrz operatora i jako tie-break —
                // od najnowszych. Stabilne przy paginacji (tie-break po id).
                $query->orderByRaw("ROW_NUMBER() OVER (PARTITION BY owner_email ORDER BY created_at DESC, id DESC) ASC")
                      ->orderBy('created_at', 'desc')
                      ->orderBy('id', 'desc');
        }

        $perPage = min(max((int) $request->input('per_page', 24), 1), 200);
        return $query->paginate($perPage);
    }

    /**
     * Lightweight map pins endpoint — returns only fields needed for map markers/popups.
     * Same filters as index(), no pagination, max 2000 results, only ads with coordinates.
     */
    public function mapPins(Request $request)
    {
        $query = $this->buildFilteredQuery($request);

        $query->whereNotNull('latitude')
              ->whereNotNull('longitude')
              ->where('latitude', '!=', 0)
              ->where('longitude', '!=', 0);

        return $query
            ->orderBy('created_at', 'desc')
            ->limit(2000)
            ->get(['id', 'latitude', 'longitude', 'type', 'title', 'city', 'location', 'price', 'price_unit', 'image_url']);
    }

    /**
     * Build a filtered query from request parameters (shared by index and mapPins).
     */
    private function buildFilteredQuery(Request $request)
    {
        $query = Advertisement::where('is_active', 1);

        // --- Type filter ---
        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        // --- City filter ---
        if ($request->filled('city')) {
            $city = $request->input('city');
            if ($request->boolean('city_strict')) {
                $query->whereRaw('LOWER(city) = ?', [mb_strtolower($city)]);
            } else {
                $query->whereRaw('LOWER(city) LIKE ?', ['%' . mb_strtolower($city) . '%']);
            }
        }

        // --- Distance filter ---
        if ($request->filled('lat') && $request->filled('lng') && $request->filled('radius')) {
            $lat = (float) $request->input('lat');
            $lng = (float) $request->input('lng');
            $radius = (float) $request->input('radius', 30);
            $query->whereRaw("
                (6371 * 2 * ASIN(SQRT(
                    POWER(SIN((RADIANS(?) - RADIANS(latitude)) / 2), 2) +
                    COS(RADIANS(?)) * COS(RADIANS(latitude)) *
                    POWER(SIN((RADIANS(?) - RADIANS(longitude)) / 2), 2)
                ))) <= ?
            ", [$lat, $lat, $lng, $radius]);
        }

        // --- Region filter ---
        if ($request->filled('region')) {
            $query->where('region', $request->input('region'));
        }

        // --- Status filter ---
        if ($request->filled('status')) {
            $statuses = array_map(
                fn($s) => trim($s) === 'available' ? 'active' : trim($s),
                explode(',', $request->input('status'))
            );
            $query->where(function ($q) use ($statuses) {
                foreach ($statuses as $status) {
                    if ($status === 'active') {
                        $q->orWhere('status', 'active')
                          ->orWhere(function ($inner) {
                              $inner->where('status', 'soon_available')
                                    ->where(function ($d) {
                                        $d->whereNull('available_from')
                                          ->orWhere('available_from', '<=', now());
                                    });
                          });
                    } else {
                        $q->orWhere('status', $status);
                    }
                }
            });
        }

        // --- Keyword search ---
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%$search%")
                  ->orWhere('description', 'LIKE', "%$search%")
                  ->orWhere('location', 'LIKE', "%$search%");
            });
        }

        // --- Map bounds filter ---
        if ($request->filled('map_north') && $request->filled('map_south') &&
            $request->filled('map_east') && $request->filled('map_west')) {
            $query->whereBetween('latitude', [(float) $request->input('map_south'), (float) $request->input('map_north')])
                  ->whereBetween('longitude', [(float) $request->input('map_west'), (float) $request->input('map_east')]);
        }

        // --- Exact match filters ---
        $exactFilters = [
            'orientation', 'variant', 'road_class', 'offer_type',
            'traffic_intensity', 'environment', 'transport_scope',
            'mobile_exposure_mode', 'operating_zone', 'rental_period',
            'lighting_type', 'lighting_type_banner',
        ];
        foreach ($exactFilters as $param) {
            if ($request->filled($param)) {
                $query->where($param, $request->input($param));
            }
        }

        // --- Location tier (virtual: PREMIUM = high traffic + major road) ---
        if ($request->filled('location_tier')) {
            if ($request->input('location_tier') === 'PREMIUM') {
                $query->where('traffic_intensity', 'high')
                      ->whereIn('road_class', ['highway', 'expressway', 'national']);
            } else {
                $query->where(function ($q) {
                    $q->where('traffic_intensity', '!=', 'high')
                      ->orWhereNotIn('road_class', ['highway', 'expressway', 'national'])
                      ->orWhereNull('road_class');
                });
            }
        }

        // --- Boolean filters ---
        $booleanFilters = [
            'has_image', 'price_includes_print', 'price_includes_mounting',
            'graphic_design_help', 'has_vat_invoice', 'ambient_light_control',
        ];
        foreach ($booleanFilters as $param) {
            if ($request->has($param) && $request->input($param) !== null && $request->input($param) !== '') {
                $query->where($param, (bool) $request->input($param));
            }
        }

        // --- Has backlight ---
        if ($request->boolean('has_backlight')) {
            $query->where(function ($q) {
                $q->where('has_backlight', true)
                  ->orWhere(function ($i) {
                      $i->whereNotNull('lighting_type')
                        ->where('lighting_type', '!=', '')
                        ->where('lighting_type', '!=', 'none');
                  })
                  ->orWhere(function ($i) {
                      $i->whereNotNull('lighting_type_banner')
                        ->where('lighting_type_banner', '!=', '')
                        ->where('lighting_type_banner', '!=', 'none');
                  });
            });
        }

        // --- Price range (with unit conversion to per-day base) ---
        if ($request->filled('price_from') || $request->filled('price_to')) {
            $priceUnit = $request->input('price_unit', 'month');
            $factors = ['day' => 1, 'week' => 7, 'month' => 30, 'year' => 365, 'campaign' => 30, 'sqm' => 30];
            $factor = $factors[$priceUnit] ?? 30;
            $pricePerDaySql = "
                CASE
                    WHEN price_unit = 'day'      THEN price
                    WHEN price_unit = 'week'     THEN price / 7.0
                    WHEN price_unit = 'month'    THEN price / 30.0
                    WHEN price_unit = 'year'     THEN price / 365.0
                    WHEN price_unit = 'campaign' THEN price / COALESCE(NULLIF(campaign_duration, 0), 30.0)
                    ELSE price / 30.0
                END
            ";
            if ($request->filled('price_from')) {
                $query->whereRaw("($pricePerDaySql) >= ?", [(float) $request->input('price_from') / $factor]);
            }
            if ($request->filled('price_to')) {
                $query->whereRaw("($pricePerDaySql) <= ?", [(float) $request->input('price_to') / $factor]);
            }
        }

        // --- Numeric range filters ---
        $rangeFilters = [
            'width_from'            => ['col' => 'width',            'op' => '>='],
            'width_to'              => ['col' => 'width',            'op' => '<='],
            'height_from'           => ['col' => 'height',           'op' => '>='],
            'height_to'             => ['col' => 'height',           'op' => '<='],
            'pixel_pitch_from'      => ['col' => 'pixel_pitch',      'op' => '>='],
            'pixel_pitch_to'        => ['col' => 'pixel_pitch',      'op' => '<='],
            'brightness_from'       => ['col' => 'brightness',       'op' => '>='],
            'brightness_to'         => ['col' => 'brightness',       'op' => '<='],
            'vehicle_count_from'    => ['col' => 'vehicle_count',    'op' => '>='],
            'vehicle_count_to'      => ['col' => 'vehicle_count',    'op' => '<='],
            'campaign_duration_from'=> ['col' => 'campaign_duration','op' => '>='],
            'campaign_duration_to'  => ['col' => 'campaign_duration','op' => '<='],
            'daily_passengers_from' => ['col' => 'daily_passengers', 'op' => '>='],
            'daily_passengers_to'   => ['col' => 'daily_passengers', 'op' => '<='],
        ];
        foreach ($rangeFilters as $param => $cfg) {
            if ($request->filled($param)) {
                $query->where($cfg['col'], $cfg['op'], (float) $request->input($param));
            }
        }

        // --- Surface area ---
        if ($request->filled('surface_from')) {
            $query->whereRaw('(width * height) >= ?', [(float) $request->input('surface_from')]);
        }
        if ($request->filled('surface_to')) {
            $query->whereRaw('(width * height) <= ?', [(float) $request->input('surface_to')]);
        }

        // --- Traffic direction (JSON array field) ---
        if ($request->filled('traffic_direction')) {
            $dir = $request->input('traffic_direction');
            if ($dir === 'both') {
                $query->whereJsonContains('traffic_direction', 'entry')
                      ->whereJsonContains('traffic_direction', 'exit');
            } else {
                $query->where(function ($q) use ($dir) {
                    $q->whereJsonContains('traffic_direction', $dir)
                      ->orWhereJsonContains('traffic_direction', 'both');
                });
            }
        }

        // --- Traffic type (JSON array field) ---
        if ($request->filled('traffic_type')) {
            $ttype = $request->input('traffic_type');
            if ($ttype === 'both') {
                $query->whereJsonContains('traffic_type', 'pedestrian')
                      ->whereJsonContains('traffic_type', 'vehicular');
            } else {
                $query->where(function ($q) use ($ttype) {
                    $q->whereJsonContains('traffic_type', $ttype)
                      ->orWhereJsonContains('traffic_type', 'both');
                });
            }
        }

        return $query;
    }

    public function store(Request $request)
    {
        // Types that require variant field
        $type = $request->input('type');
        $typesWithVariant = ['billboard', 'citylight', 'led_screen', 'totem', 'transport', 'mobile'];
        $requiresVariant = in_array($type, $typesWithVariant);
        $requiresRoadClass = $type === 'billboard';

        $validated = $request->validate([
            'title' => ['required', 'string', new ProfanityRule],
            'type' => 'required|in:billboard,citylight,led_screen,banner,wall,totem,transport,mobile,other',
            'location' => 'required|string',
            'city' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'description' => ['required', 'string', new ProfanityRule],
            'price' => 'required|numeric|min:0|max:999999',
            'width' => 'nullable|numeric|min:0|max:500',
            'height' => 'nullable|numeric|min:0|max:100',
            'owner_email' => 'required|email',
            'price_unit' => 'required|in:day,week,month,year,sqm,campaign',
            'region' => 'nullable|string',
            'orientation' => 'required|string',
            'traffic_intensity' => 'nullable|in:low,medium,high',
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
            'road_class' => $requiresRoadClass ? 'required|in:highway,expressway,national,regional,local,urban' : 'nullable|in:highway,expressway,national,regional,local,urban',
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
            // Extended surface fields
            'lighting_type' => 'nullable|in:led,fluorescent,natural,none',
            'daily_passengers' => 'nullable|integer|min:0',
            'operating_zone' => 'nullable|in:center,periphery,agglomeration',
            'ambient_light_control' => 'nullable|boolean',
            // Lighting type dla banerów i ścian
            'lighting_type_banner' => 'nullable|in:none,backlight,frontlight',
            'estimated_daily_views' => 'nullable|integer',
        ]);


        // Zabezpieczenie przed nieumyślnym dublem (refresh, autofill, dwie karty,
        // klient zerwał połączenie i ponowił). Wymagamy zgodności wszystkich
        // czterech pól: właściciel + lokalizacja + tytuł, w oknie 5 minut.
        // Tytuł jest potrzebny, bo użytkownicy bez precyzyjnej pinezki mogą
        // dostać centroid miasta (np. "Warszawa") i dwa różne ogłoszenia
        // miałyby wtedy identyczne lat/lng.
        $existing = Advertisement::where('owner_email', $validated['owner_email'])
            ->where('latitude', $validated['latitude'])
            ->where('longitude', $validated['longitude'])
            ->where('title', $validated['title'])
            ->where('created_at', '>=', now()->subMinutes(5))
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Przed chwilą dodałeś ogłoszenie w tej lokalizacji. Sprawdź skrzynkę — link do zarządzania został wysłany na e-mail.',
                'duplicate_id' => $existing->id,
            ], 409);
        }

        // Set defaults if not present (though migration has defaults, explicit is good)
        $validated['status'] = $request->input('status', 'active');
        $validated['is_active'] = $request->input('is_active', true);

        $ad = Advertisement::create($validated);

        // Generate slug after the ad is created and has an ID
        $ad->slug = Str::slug($ad->title) . '-' . $ad->id;
        $ad->save();

        try {
            Mail::to($ad->owner_email)->send(new AdCreatedConfirmationMail($ad));
        } catch (\Exception $e) {
            \Log::error('Error sending ad creation confirmation email: ' . $e->getMessage());
        }

        if ($adminEmail = config('mail.admin_notification_email')) {
            try {
                Mail::to($adminEmail)->send(new NewAdvertisementNotificationMail($ad));
            } catch (\Exception $e) {
                \Log::error('Error sending admin notification email: ' . $e->getMessage());
            }
        }

        // Check for search alerts
        try {
            (new SearchAlertService())->checkAlerts($ad);
        } catch (\Exception $e) {
            \Log::error('Error checking search alerts: ' . $e->getMessage());
        }


        // Handle newsletter subscription
        if ($request->input('subscribe_newsletter')) {
            try {
                // Check if already subscribed
                if (!Newsletter::where('email', $ad->owner_email)->exists()) {
                    Newsletter::create([
                        'email' => $ad->owner_email,
                        'unsubscribe_token' => \Illuminate\Support\Str::random(40),
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('Error creating newsletter subscription from ad creation: ' . $e->getMessage());
            }
        }

        // Clear sitemap cache and notify Google
        $this->notifySearchEngines();

        return response()->json($ad, 201);

    }

    public function show(string $id)
    {
        return Advertisement::where('is_active', 1)->findOrFail($id);
    }

    public function update(Request $request, string $id)
    {
        // Types that require variant field
        $type = $request->input('type');
        $typesWithVariant = ['billboard', 'citylight', 'led_screen', 'totem', 'transport', 'mobile'];
        $requiresVariant = in_array($type, $typesWithVariant);
        $requiresRoadClass = $type === 'billboard';

        $validated = $request->validate([
            'title' => ['required', 'string', new ProfanityRule],
            'type' => 'required|in:billboard,citylight,led_screen,banner,wall,totem,transport,mobile,other',
            'location' => 'required|string',
            'city' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'description' => ['required', 'string', new ProfanityRule],
            'price' => 'required|numeric|min:0|max:999999',
            'width' => 'nullable|numeric|min:0|max:500',
            'height' => 'nullable|numeric|min:0|max:100',
            'owner_email' => 'required|email',
            'price_unit' => 'required|in:day,week,month,year,sqm,campaign',
            'region' => 'nullable|string',
            'orientation' => 'required|string',
            'traffic_intensity' => 'nullable|in:low,medium,high',
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
            'road_class' => $requiresRoadClass ? 'required|in:highway,expressway,national,regional,local,urban' : 'nullable|in:highway,expressway,national,regional,local,urban',
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
            // Extended surface fields
            'lighting_type' => 'nullable|in:led,fluorescent,natural,none',
            'daily_passengers' => 'nullable|integer|min:0',
            'operating_zone' => 'nullable|in:center,periphery,agglomeration',
            'ambient_light_control' => 'nullable|boolean',
            // Lighting type dla banerów i ścian
            'lighting_type_banner' => 'nullable|in:none,backlight,frontlight',
            'estimated_daily_views' => 'nullable|integer',
        ]);

        $ad = Advertisement::findOrFail($id);

        // Check if location changed (latitude or longitude)
        $locationChanged = $ad->latitude != $validated['latitude'] || $ad->longitude != $validated['longitude'];

        // Reset verification status when ad is edited
        $validated['is_verified'] = false;

        $ad->update($validated);

        // Lokalizacja się zmieniła — kasujemy stary screenshot mapy. Nowy zostanie
        // wygenerowany leniwie przy najbliższym żądaniu PDF (generatePdf()).
        if ($locationChanged && $ad->map_screenshot_path) {
            $oldPath = storage_path('app/public/' . $ad->map_screenshot_path);
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
            $ad->map_screenshot_path = null;
            $ad->save();
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

    public function incrementViews(Request $request, string $id)
    {
        Advertisement::findOrFail($id); // Verify ad exists

        $ip = $request->ip();
        $cacheKey = "ad_{$id}_view_{$ip}";

        // Sprawdzamy czy ten IP oglądał to ogłoszenie w ciągu ostatniej godziny
        if (!Cache::has($cacheKey)) {
            // Zapisz statystykę dzienną
            $dailyStat = \App\Models\AdvertisementDailyStat::getTodayOrCreate($id);
            $dailyStat->increment('views');

            // Zablokuj kolejne naliczanie na 1 godzinę (3600 sekund) po czym cache wygaśnie
            Cache::put($cacheKey, true, now()->addHour());

            return response()->json(['message' => 'Views incremented']);
        }

        return response()->json(['message' => 'View already counted for this IP recently']);
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

        // Priority 1: Same city AND same type
        $similar = Advertisement::where('city', $ad->city)
            ->where('type', $ad->type)
            ->where('id', '!=', $id)
            ->where('is_active', 1)
            ->limit(4)
            ->get();

        // If we have less than 4, add same type from other cities
        if ($similar->count() < 4) {
            $excludeIds = $similar->pluck('id')->push($id)->toArray();
            
            $sameType = Advertisement::where('type', $ad->type)
                ->whereNotIn('id', $excludeIds)
                ->where('is_active', 1)
                ->limit(4 - $similar->count())
                ->get();
            
            $similar = $similar->merge($sameType);
        }

        // If still less than 4, add same city (any type)
        if ($similar->count() < 4) {
            $excludeIds = $similar->pluck('id')->push($id)->toArray();
            
            $sameCity = Advertisement::where('city', $ad->city)
                ->whereNotIn('id', $excludeIds)
                ->where('is_active', 1)
                ->limit(4 - $similar->count())
                ->get();
            
            $similar = $similar->merge($sameCity);
        }

        return $similar;
    }

    public function report(Request $request)
    {
        $validated = $request->validate([
            'advertisement_id' => 'required|exists:advertisements,id',
            'reason' => 'required|string',
            'details' => 'nullable|string',
        ]);

        if (!isset($validated['details'])) {
            $validated['details'] = '';
        }

        $report = \App\Models\Report::create($validated);

        if ($adminEmail = config('mail.admin_notification_email')) {
            try {
                Mail::to($adminEmail)->send(new AdminReportNotificationMail($report));
            } catch (\Exception $e) {
                \Log::error('Error sending admin report notification: ' . $e->getMessage());
            }
        }

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

        $feedback = \App\Models\Feedback::create([
            'type' => $validated['type'],
            'email' => $validated['email'],
            'message' => $validated['message'],
            'url' => $validated['url'] ?? null,
            'user_agent' => $validated['userAgent'] ?? null,
        ]);

        Mail::to($feedback->email)->send(new FeedbackConfirmationMail($feedback));

        if ($adminEmail = config('mail.admin_notification_email')) {
            try {
                Mail::to($adminEmail)->send(new AdminFeedbackNotificationMail($feedback));
            } catch (\Exception $e) {
                \Log::error('Error sending admin feedback notification: ' . $e->getMessage());
            }
        }

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
        ], [
            'email.unique' => 'Ten adres e-mail jest już zapisany w naszym newsletterze.',
            'email.required' => 'Adres e-mail jest wymagany.',
            'email.email' => 'Podaj prawidłowy adres e-mail.',
        ]);

        try {
            $token = \Illuminate\Support\Str::random(40);

            \App\Models\Newsletter::create([
                'email' => $validated['email'],
                'unsubscribe_token' => $token,
            ]);

            \Illuminate\Support\Facades\Mail::to($validated['email'])
                ->send(new \App\Mail\NewsletterConfirmationMail($token));

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

        // Screenshot mapy generujemy leniwie — dopiero gdy faktycznie potrzebny
        // do PDF. Większość ogłoszeń nigdy nie wygeneruje PDF, więc nie ma sensu
        // robić go przy każdym dodawaniu/edycji.
        $needsScreenshot = !$ad->map_screenshot_path
            || !is_file(storage_path('app/public/' . $ad->map_screenshot_path));

        if ($needsScreenshot) {
            try {
                $this->generateMapScreenshot($ad);
            } catch (\Throwable $e) {
                \Log::error('Lazy map screenshot generation failed for ad ' . $ad->id . ': ' . $e->getMessage());
                // PDF wygenerujemy bez mapy — szablon i tak ma fallback na if($advertisement->map_screenshot_path)
            }
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.advertisement', ['advertisement' => $ad]);
        return $pdf->download('ogloszenie-' . $ad->id . '.pdf');
    }

    public function generateComparisonPdf(Request $request)
    {
        $ids = explode(',', $request->input('ids'));
        $unit = $request->input('unit', 'month');

        // Pobierz ogłoszenia i zachowaj kolejność z parametru ids
        $ads = Advertisement::whereIn('id', $ids)->get();

        // Sortuj ogłoszenia w kolejności z parametru ids
        $adsOrdered = collect();
        foreach ($ids as $id) {
            $ad = $ads->firstWhere('id', $id);
            if ($ad) {
                $adsOrdered->push($ad);
            }
        }

        // Jeśli fields nie są podane, wyświetl wszystkie pola
        $fields = $request->has('fields') ? explode(',', $request->input('fields')) : [
            'price',
            'price_per_sqm',
            'type',
            'variant',
            'dimensions',
            'surface_area',
            'orientation',
            'location',
            'location_tier',
            'road_class',
            'traffic_intensity',
            'traffic_direction',
            'traffic_type',
            'environment',
            'has_backlight',
            'price_includes_print',
            'price_includes_mounting',
            'graphic_design_help',
            'status',
            'offer_type',
            'has_vat_invoice',
            'transport_scope',
            'vehicle_count',
            'mobile_exposure_mode',
            'route_area',
            'operating_hours',
            'resolution',
            'pixel_pitch',
            'brightness',
            'lighting_type',
            'daily_passengers',
            'operating_zone',
            'ambient_light_control',
            'lighting_type_banner'
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.comparison', [
            'advertisements' => $adsOrdered,
            'displayUnit' => $unit,
            'visibleFields' => $fields
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

            // Send confirmation copy to the sender
            try {
                Mail::send('emails.contact_confirmation', [
                    'adTitle' => $ad->title,
                    'adUrl' => $advertisementUrl,
                    'message' => $validated['message'],
                ], function ($mail) use ($validated) {
                    $mail->to($validated['email'])
                        ->subject('Potwierdzenie: Twoje zapytanie zostało wysłane — ReklaMap');
                });
            } catch (\Exception $e) {
                // Log but don't fail the main response — confirmation is secondary
                \Log::warning('Could not send contact confirmation to sender: ' . $e->getMessage());
            }

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

            $generator = new \App\Services\StaticMapGenerator();
            $result = $generator->generate(
                (float) $ad->latitude,
                (float) $ad->longitude,
                $fullPath
            );

            if (!$result) {
                throw new \RuntimeException('StaticMapGenerator failed to produce the image.');
            }

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

        // Cache silosów (SilosController) wygasa naturalnie po 1h — to akceptowalne
        // opóźnienie dla nowo dodanych kombinacji typ × miasto.

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
