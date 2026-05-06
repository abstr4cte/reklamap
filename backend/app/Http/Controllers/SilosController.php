<?php

namespace App\Http\Controllers;

use App\Models\Advertisement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Endpoint dostarcza dane do silosów internal linkingu (komponent RelatedSilos
 * na froncie). Zwraca tylko kombinacje typ × miasto z aktywnymi ogłoszeniami,
 * żeby nie linkować do thin content.
 */
class SilosController extends Controller
{
    /**
     * Mapowanie slugów URL → typ w bazie. Spójne z routingiem frontendu i
     * generatorem sitemap.xml (backend/routes/web.php).
     *
     * @var array<string, string>
     */
    private const TYPE_SLUG_TO_DB = [
        'billboardy' => 'billboard',
        'citylighty' => 'citylight',
        'banery' => 'banner',
        'sciany-reklamowe' => 'wall',
        'totemy-reklamowe' => 'totem',
        'reklama-w-transporcie' => 'transport',
        'reklama-mobilna' => 'mobile',
        'ekrany-led' => 'led_screen',
        'inne' => 'other',
    ];

    /**
     * Etykiety wyświetlane w UI dla każdego typu.
     *
     * @var array<string, string>
     */
    private const TYPE_LABELS = [
        'billboard' => 'Billboardy',
        'citylight' => 'Citylighty',
        'banner' => 'Banery',
        'wall' => 'Ściany reklamowe',
        'totem' => 'Totemy reklamowe',
        'transport' => 'Reklama w transporcie',
        'mobile' => 'Reklama mobilna',
        'led_screen' => 'Ekrany LED',
        'other' => 'Inne',
    ];

    public function index(Request $request): JsonResponse
    {
        $typeSlug = $request->query('type');
        $citySlug = $request->query('city');

        $dbType = $typeSlug ? (self::TYPE_SLUG_TO_DB[$typeSlug] ?? null) : null;

        $cacheKey = sprintf('silos:%s:%s', $dbType ?? 'all', $citySlug ?? 'all');

        $payload = Cache::remember($cacheKey, 3600, function () use ($dbType, $citySlug) {
            return [
                'other_cities' => $this->buildOtherCities($dbType, $citySlug),
                'other_types' => $this->buildOtherTypes($dbType, $citySlug),
            ];
        });

        return response()->json($payload);
    }

    /**
     * Top 15 miast z aktywnymi ogłoszeniami. Filtruje po typie (jeśli podany)
     * i wyklucza aktualnie wybrane miasto.
     *
     * @return array<int, array{city: string, slug: string, count: int}>
     */
    private function buildOtherCities(?string $dbType, ?string $excludeCitySlug): array
    {
        $query = Advertisement::where('is_active', true);
        if ($dbType) {
            $query->where('type', $dbType);
        }

        $rows = $query
            ->select('city', DB::raw('count(*) as count'))
            ->groupBy('city')
            ->orderByDesc('count')
            ->orderBy('city')
            ->get();

        return $rows
            ->map(fn ($row) => [
                'city' => $row->city,
                'slug' => Str::slug($row->city),
                'count' => (int) $row->count,
            ])
            ->reject(fn ($row) => $excludeCitySlug && $row['slug'] === $excludeCitySlug)
            ->take(15)
            ->values()
            ->all();
    }

    /**
     * Wszystkie typy z aktywnymi ogłoszeniami w danym mieście. Renderowane
     * tylko gdy w URL jest miasto (inaczej pusta tablica). Wyklucza aktualnie
     * wybrany typ.
     *
     * @return array<int, array{type: string, slug: string, label: string, count: int}>
     */
    private function buildOtherTypes(?string $excludeDbType, ?string $citySlug): array
    {
        if (! $citySlug) {
            return [];
        }

        $cityName = $this->resolveCityName($citySlug);
        if (! $cityName) {
            return [];
        }

        $rows = Advertisement::where('is_active', true)
            ->whereRaw('LOWER(city) = ?', [mb_strtolower($cityName)])
            ->select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->orderByDesc('count')
            ->get();

        $typeDbToSlug = array_flip(self::TYPE_SLUG_TO_DB);

        return $rows
            ->reject(fn ($row) => $excludeDbType && $row->type === $excludeDbType)
            ->map(fn ($row) => [
                'type' => $row->type,
                'slug' => $typeDbToSlug[$row->type] ?? 'inne',
                'label' => self::TYPE_LABELS[$row->type] ?? 'Inne',
                'count' => (int) $row->count,
            ])
            ->values()
            ->all();
    }

    /**
     * Slug miasta z URL (np. "warszawa") → pełna nazwa z polskimi znakami
     * z bazy (np. "Warszawa", "Kraków"). Bez pełnego mapowania w obie strony
     * polskich znaków robimy reverse lookup po Str::slug.
     */
    private function resolveCityName(string $citySlug): ?string
    {
        $map = Cache::remember('silos:city_slug_map', 3600, function () {
            return Advertisement::where('is_active', true)
                ->select('city')
                ->distinct()
                ->pluck('city')
                ->mapWithKeys(fn ($city) => [Str::slug($city) => $city])
                ->toArray();
        });

        return $map[$citySlug] ?? null;
    }
}
