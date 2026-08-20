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
            'contact_preference',
            'offer_type',
            'orientation',
            'road_class',
            'is_active',
            'created_at',
            'updated_at',
        ]);

        // Anti-scraping: owner_email i phone NIE mogą wyciekać w publicznej odpowiedzi
        // (ukryte w modelu, odsłaniane tylko na szczegółach/panelu właściciela).
        $response->assertJsonMissingPath('owner_email');
        $response->assertJsonMissingPath('phone');

        // Verify data types
        $response->assertJson([
            'title' => 'Test Billboard in Warsaw',
            'type' => 'billboard',
            'price' => 1000,
            'is_active' => true,
        ]);
    }

    /**
     * Test default sort diversifies by operator (B-6 anti-flood):
     * a single operator with a large portfolio must not push smaller
     * operators off the first page. Without diversification the lone
     * small-operator ad would land in 6th place (pure created_at DESC);
     * with it, it is interleaved into the top of the list.
     */
    public function test_default_sort_diversifies_by_operator(): void
    {
        // Duży operator — 5 nośników, wszystkie najnowsze
        Advertisement::factory()->count(5)->create([
            'is_active' => true,
            'owner_email' => 'big@operator.pl',
            'created_at' => now(),
        ]);
        // Mały operator — 1 nośnik, starszy (bez dywersyfikacji byłby ostatni)
        $small = Advertisement::factory()->create([
            'is_active' => true,
            'owner_email' => 'small@operator.pl',
            'created_at' => now()->subDay(),
        ]);

        $response = $this->getJson('/api/listings', $this->appKeyHeaders());

        $response->assertStatus(200);
        $ids = array_column($response->json('data'), 'id');
        $this->assertContains(
            $small->id,
            array_slice($ids, 0, 2),
            'Mały operator powinien być przeplatany na górze listy, nie zepchnięty na koniec przez duże portfolio.'
        );
    }

    /**
     * Test explicit ?sort=newest is pure chronological (NOT diversified).
     * The diversified order is reserved for the default ("Polecane") mode.
     */
    public function test_explicit_newest_sort_is_pure_chronological(): void
    {
        Advertisement::factory()->count(5)->create([
            'is_active' => true,
            'owner_email' => 'big@operator.pl',
            'created_at' => now(),
        ]);
        $small = Advertisement::factory()->create([
            'is_active' => true,
            'owner_email' => 'small@operator.pl',
            'created_at' => now()->subDay(),
        ]);

        $response = $this->getJson('/api/listings?sort=newest', $this->appKeyHeaders());

        $response->assertStatus(200);
        $ids = array_column($response->json('data'), 'id');
        $this->assertNotContains(
            $small->id,
            array_slice($ids, 0, 2),
            'Jawny "Najnowsze" = czysta data: starszy nośnik małego operatora nie powinien trafić w top 2.'
        );
    }

    /**
     * Regresja: city_strict musi łapać miasta z polskimi znakami, gdy slug w URL jest ASCII.
     * slugify robi "Kłodzko"→"klodzko"; deslugify na froncie odtwarza diakrytyki tylko dla miast
     * z cityMap, więc reszta (długi ogon) trafia do API jako "Klodzko" (bez ł). Wcześniej
     * LOWER(city)=? nie łapało "Kłodzko" → 0 ofert (pusta strona miasta dla usera + fałszywy
     * noindex w prerenderze). Fold polskich znaków po obu stronach to naprawia.
     */
    public function test_city_strict_filter_matches_polish_diacritics_from_ascii_slug(): void
    {
        Advertisement::factory()->create(['city' => 'Kłodzko', 'is_active' => true, 'status' => 'active']);
        Advertisement::factory()->create(['city' => 'Kłodzko', 'is_active' => true, 'status' => 'active']);
        Advertisement::factory()->create(['city' => 'Koszalin', 'is_active' => true, 'status' => 'active']);

        // ASCII — dokładnie to, co SPA wysyła dla miasta spoza cityMap
        $ascii = $this->getJson('/api/listings?city=Klodzko&city_strict=1', $this->appKeyHeaders());
        $ascii->assertStatus(200);
        $this->assertSame(2, $ascii->json('total'), 'ASCII "Klodzko" musi złapać "Kłodzko".');

        // Wariant z diakrytykiem też działa
        $diacritic = $this->getJson('/api/listings?' . http_build_query(['city' => 'Kłodzko', 'city_strict' => 1]), $this->appKeyHeaders());
        $this->assertSame(2, $diacritic->json('total'), 'Diakrytyczne "Kłodzko" też musi działać.');

        // Kontrola: inne miasto nie łapie fałszywie
        $other = $this->getJson('/api/listings?city=Koszalin&city_strict=1', $this->appKeyHeaders());
        $this->assertSame(1, $other->json('total'), 'Koszalin nie może łapać Kłodzka.');
    }

    /**
     * Regresja: miasta z myślnikiem w nazwie ("Szklary-Huta") — deslugify slugu "szklary-huta"
     * daje "Szklary Huta" (spacja), więc dopasowanie musi traktować myślnik i spację wymiennie.
     */
    public function test_city_strict_filter_matches_hyphenated_city_from_spaced_slug(): void
    {
        Advertisement::factory()->create(['city' => 'Szklary-Huta', 'is_active' => true, 'status' => 'active']);

        // deslugify("szklary-huta") = "Szklary Huta" (spacja) — musi złapać "Szklary-Huta"
        $spaced = $this->getJson('/api/listings?' . http_build_query(['city' => 'Szklary Huta', 'city_strict' => 1]), $this->appKeyHeaders());
        $spaced->assertStatus(200);
        $this->assertSame(1, $spaced->json('total'), 'Spacjowane "Szklary Huta" musi złapać "Szklary-Huta".');
    }

    /**
     * Pilot "promień aglomeracji" (Katowice): `include_nearby=1` zwraca ogłoszenia z sąsiednich
     * miast w osobnym polu `nearby_listings`, NIE dotykając głównego `data`/`total` — to musi
     * pozostać wyłącznie liczbą własnych ogłoszeń miasta (podstawa THIN_PAGE_THRESHOLD/noindex
     * we froncie). Sosnowiec (~9 km od Katowic) łapie się w promieniu 30 km, Warszawa nie.
     */
    public function test_include_nearby_returns_separate_field_without_affecting_main_count(): void
    {
        Advertisement::factory()->count(3)->create([
            'city' => 'Katowice', 'latitude' => 50.2649, 'longitude' => 19.0238,
            'is_active' => true, 'status' => 'active',
        ]);
        Advertisement::factory()->count(2)->create([
            'city' => 'Sosnowiec', 'latitude' => 50.2863, 'longitude' => 19.1042,
            'is_active' => true, 'status' => 'active',
        ]);
        Advertisement::factory()->create([
            'city' => 'Warszawa', 'latitude' => 52.2297, 'longitude' => 21.0122,
            'is_active' => true, 'status' => 'active',
        ]);

        $withNearby = $this->getJson(
            '/api/listings?' . http_build_query(['city' => 'Katowice', 'city_strict' => 1, 'include_nearby' => 1]),
            $this->appKeyHeaders()
        );
        $withNearby->assertStatus(200);
        $this->assertSame(3, $withNearby->json('total'), 'Główny licznik miasta musi zostać wyłącznie własnymi ogłoszeniami.');
        $nearby = $withNearby->json('nearby_listings');
        $this->assertCount(2, $nearby, 'Sosnowiec (~9 km) musi się złapać w promieniu 30 km, Warszawa nie.');
        foreach ($nearby as $ad) {
            $this->assertSame('Sosnowiec', $ad['city']);
        }

        // Bez include_nearby pole w ogóle nie występuje (surowy paginator, jak dziś).
        $withoutNearby = $this->getJson(
            '/api/listings?' . http_build_query(['city' => 'Katowice', 'city_strict' => 1]),
            $this->appKeyHeaders()
        );
        $withoutNearby->assertStatus(200);
        $this->assertArrayNotHasKey('nearby_listings', $withoutNearby->json());
    }

    /**
     * Duże miasto BEZ własnej podaży (prod 2026-08-20: Kraków, Gdańsk, Łódź, Szczecin,
     * Bydgoszcz, Białystok, Gliwice = 0 ofert) też musi pokazać oferty z okolicy — centroidu
     * nie ma z czego policzyć, więc backend schodzi do słownika współrzędnych dużych miast.
     * Strona zostaje `noindex` (0 własnych < próg 3) — to świadome; sekcja służy ruchowi
     * spoza wyszukiwarki (blog, social, direct), nie obchodzeniu progu cienkiej strony.
     */
    public function test_include_nearby_falls_back_to_city_dictionary_when_city_has_no_supply(): void
    {
        // Wieliczka ~13 km od centrum Krakowa; Gdańsk ~500 km — kontrola negatywna.
        Advertisement::factory()->count(2)->create([
            'city' => 'Wieliczka', 'latitude' => 49.9871, 'longitude' => 20.0649,
            'is_active' => true, 'status' => 'active',
        ]);
        Advertisement::factory()->create([
            'city' => 'Gdańsk', 'latitude' => 54.3520, 'longitude' => 18.6466,
            'is_active' => true, 'status' => 'active',
        ]);

        $response = $this->getJson(
            '/api/listings?' . http_build_query(['city' => 'Kraków', 'city_strict' => 1, 'include_nearby' => 1]),
            $this->appKeyHeaders()
        );

        $response->assertStatus(200);
        $this->assertSame(0, $response->json('total'), 'Kraków nie ma własnych ofert — główny licznik musi zostać zerem (strona pozostaje noindex).');

        $nearby = $response->json('nearby_listings');
        $this->assertCount(2, $nearby, 'Mimo zera własnych ofert sekcja "w okolicy" musi znaleźć Wieliczkę przez słownik współrzędnych.');
        foreach ($nearby as $ad) {
            $this->assertSame('Wieliczka', $ad['city'], 'Gdańsk (~500 km) nie może się załapać.');
        }
    }

    /**
     * Miasto poniżej progu cienkiej strony (prod: Warszawa 2, Lublin 1, Częstochowa 2) —
     * strona jest praktycznie pusta tak samo jak przy zerze, więc gdy w 30 km nic nie ma,
     * schodzimy do 50 km. Miasta z realną podażą (Katowice 8) zostają przy 30 km.
     */
    public function test_include_nearby_widens_radius_for_thin_city_but_not_for_supplied_one(): void
    {
        // Warszawa: 1 własna oferta (poniżej progu 3) + nic w 30 km, ale coś w ~44 km.
        Advertisement::factory()->create([
            'city' => 'Warszawa', 'latitude' => 52.2297, 'longitude' => 21.0122,
            'is_active' => true, 'status' => 'active',
        ]);
        Advertisement::factory()->create([
            'city' => 'Żyrardów', 'latitude' => 52.0489, 'longitude' => 20.4462,
            'is_active' => true, 'status' => 'active',
        ]);

        $thin = $this->getJson(
            '/api/listings?' . http_build_query(['city' => 'Warszawa', 'city_strict' => 1, 'include_nearby' => 1]),
            $this->appKeyHeaders()
        );
        $thin->assertStatus(200);
        $this->assertEquals(50, $thin->json('nearby_radius_km'), 'Cienkie miasto musi rozszerzyć promień do 50 km.');
        $this->assertCount(1, $thin->json('nearby_listings'));

        // Katowice: podaż powyżej progu → bez eskalacji, mimo pustki w promieniu.
        Advertisement::factory()->count(3)->create([
            'city' => 'Katowice', 'latitude' => 50.2649, 'longitude' => 19.0238,
            'is_active' => true, 'status' => 'active',
        ]);

        $supplied = $this->getJson(
            '/api/listings?' . http_build_query(['city' => 'Katowice', 'city_strict' => 1, 'include_nearby' => 1]),
            $this->appKeyHeaders()
        );
        $supplied->assertStatus(200);
        $this->assertEquals(30, $supplied->json('nearby_radius_km'), 'Miasto z podażą zostaje przy 30 km.');
    }

    /**
     * Miasto spoza słownika i bez własnej podaży nie może wysypać zapytania ani zwrócić
     * losowych ofert z drugiego końca Polski — po prostu brak sekcji.
     */
    public function test_include_nearby_returns_empty_for_unknown_city_without_supply(): void
    {
        Advertisement::factory()->create([
            'city' => 'Gdańsk', 'latitude' => 54.3520, 'longitude' => 18.6466,
            'is_active' => true, 'status' => 'active',
        ]);

        $response = $this->getJson(
            '/api/listings?' . http_build_query(['city' => 'Pcim Dolny', 'city_strict' => 1, 'include_nearby' => 1]),
            $this->appKeyHeaders()
        );

        $response->assertStatus(200);
        $this->assertSame([], $response->json('nearby_listings'));
    }

    /**
     * Regresja: filtr województwa. Front (HeroBanner/ListingsPage) wysyła ASCII-id ze słownika
     * `polishLocations.json` („dolnoslaskie”, „slaskie”), a w bazie leży `address.state` z
     * Nominatim w dwóch formatach („śląskie” ORAZ „województwo dolnośląskie”). Exact match
     * `where('region', ?)` zwracał 0 dla 13 z 16 województw (prod 2026-07-25).
     */
    public function test_region_filter_matches_ascii_id_diacritics_and_wojewodztwo_prefix(): void
    {
        Advertisement::factory()->create(['region' => 'województwo dolnośląskie', 'is_active' => true, 'status' => 'active']);
        Advertisement::factory()->create(['region' => 'dolnośląskie', 'is_active' => true, 'status' => 'active']);
        Advertisement::factory()->create(['region' => 'śląskie', 'is_active' => true, 'status' => 'active']);

        // ASCII-id z frontu musi złapać OBA zapisy dolnośląskiego, ale nie śląskie
        $ascii = $this->getJson('/api/listings?region=dolnoslaskie', $this->appKeyHeaders());
        $ascii->assertStatus(200);
        $this->assertSame(2, $ascii->json('total'), 'ASCII "dolnoslaskie" musi złapać "dolnośląskie" i "województwo dolnośląskie".');

        // Label z diakrytykami (to, co user widzi w podpowiedzi) — ten sam wynik
        $label = $this->getJson('/api/listings?' . http_build_query(['region' => 'Dolnośląskie']), $this->appKeyHeaders());
        $this->assertSame(2, $label->json('total'), 'Label "Dolnośląskie" musi dać ten sam wynik co ASCII-id.');

        // Pełna forma urzędowa też
        $full = $this->getJson('/api/listings?' . http_build_query(['region' => 'województwo dolnośląskie']), $this->appKeyHeaders());
        $this->assertSame(2, $full->json('total'), 'Pełna forma "województwo dolnośląskie" musi dać ten sam wynik.');

        // Kontrola: śląskie nie może łapać dolnośląskiego (prefiks/substring)
        $other = $this->getJson('/api/listings?region=slaskie', $this->appKeyHeaders());
        $this->assertSame(1, $other->json('total'), 'Śląskie nie może łapać dolnośląskiego.');
    }

    /** Regresja: województwa dwuczłonowe — id z myślnikiem vs zapis z bazy. */
    public function test_region_filter_matches_hyphenated_voivodeship(): void
    {
        Advertisement::factory()->create(['region' => 'województwo warmińsko-mazurskie', 'is_active' => true, 'status' => 'active']);

        $res = $this->getJson('/api/listings?region=warminsko-mazurskie', $this->appKeyHeaders());
        $res->assertStatus(200);
        $this->assertSame(1, $res->json('total'), 'ASCII "warminsko-mazurskie" musi złapać "województwo warmińsko-mazurskie".');
    }

    /**
     * Huby nawigacyjne (stopka/menu): tylko miasta/kombinacje z realną podażą (>= progu thin),
     * żeby linkowanie wewnętrzne kierowało crawl do stron z treścią, nie do pustych demand-miast.
     */
    public function test_nav_hubs_returns_only_cities_with_real_supply(): void
    {
        \Illuminate\Support\Facades\Cache::forget('nav_hubs');
        Advertisement::factory()->count(3)->create(['city' => 'Kłodzko', 'type' => 'billboard', 'is_active' => true, 'status' => 'active']);
        Advertisement::factory()->create(['city' => 'Warszawa', 'type' => 'billboard', 'is_active' => true, 'status' => 'active']); // <3

        $res = $this->getJson('/api/listings/nav-hubs', $this->appKeyHeaders());
        $res->assertStatus(200);

        $citySlugs = collect($res->json('cities'))->pluck('slug');
        $this->assertTrue($citySlugs->contains('klodzko'), 'Kłodzko (3) musi być w hubach.');
        $this->assertFalse($citySlugs->contains('warszawa'), 'Warszawa (<3) NIE może być w hubach.');

        $combos = collect($res->json('combos'));
        $this->assertTrue(
            $combos->contains(fn ($c) => $c['typeSlug'] === 'billboardy' && $c['citySlug'] === 'klodzko'),
            'Kombinacja billboardy/klodzko musi być w hubach.'
        );
    }
}
