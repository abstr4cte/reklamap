<?php

namespace App\Console\Commands;

use App\Models\Advertisement;
use App\Support\RegionCanonicalizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ⛔ NIE URUCHAMIAĆ Z `--apply` W OBECNEJ POSTACI (ustalenie 2026-07-26).
 *
 * Komenda zapisuje ASCII-id (`dolnoslaskie`), a kolumna `region` jest **wyświetlana użytkownikom**
 * w czterech miejscach — wszystkie robią tylko `ucfirst()`, bez mapowania z powrotem na etykietę:
 *   - `frontend/src/views/AdDetailPage.vue:765`   → „Kłodzko (Dolnośląskie)" na stronie ogłoszenia
 *   - `backend/resources/views/pdf/advertisement.blade.php:257`
 *   - `backend/resources/views/emails/new-advertisement-notification.blade.php:38`
 *   - `backend/resources/views/emails/search-alert.blade.php:157`
 * Po `--apply` pokazywałoby **„Kłodzko (Dolnoslaskie)"** — bez polskich znaków, także w PDF-ach
 * i mailach do klientów.
 *
 * Sam filtr województwa NIE wymaga tej kanonizacji: `AdvertisementController::foldRegion()` zwija
 * przy porównaniu wszystkie warianty („dolnoslaskie", „Dolnośląskie", „województwo dolnośląskie")
 * do wspólnej postaci, niezależnie od tego, co leży w bazie.
 *
 * DO ZROBIENIA przed użyciem: zmienić cel zapisu na **formę czytelną z diakrytykami**
 * („Dolnośląskie") zamiast ASCII-id — fold i tak załatwia filtrowanie, a wyświetlanie zostaje
 * poprawne. Faza kanonizacji już wypełnionych rekordów staje się wtedy w ogóle zbędna;
 * sensowna zostaje wyłącznie faza `--geocode` (uzupełnienie 480 pustych z lat/lng).
 *
 * ---
 *
 * Kanonizacja kolumny `advertisements.region` do ASCII-id (`slaskie`, `dolnoslaskie`, …).
 *
 * Kontekst (prod 2026-07-25): 480/827 rekordów ma `region` puste, a pozostałe 347 są w dwóch
 * formatach naraz („śląskie” 135 vs „województwo śląskie” 14). Filtr województwa we froncie
 * wysyła ASCII-id, więc 13 z 16 województw zwracało 0 ofert.
 *
 * Zasady bezpieczeństwa:
 *  - DRY-RUN domyślnie; zapis dopiero z `--apply`,
 *  - UPDATE W MIEJSCU (query builder), NIGDY delete+create — id/slug/statystyki nietknięte,
 *  - `updated_at` NIE jest ruszane (query builder go nie dotyka) — inaczej sitemapa
 *    przestawiłaby `lastmod` na setkach URL-i naraz i wyglądałaby na masową podmianę treści,
 *  - nierozpoznane wartości zostawiamy bez zmian (lepiej puste niż źle przypisane),
 *  - faza geokodowania jest opt-in (`--geocode`), 1 zapytanie/s zgodnie z polityką Nominatim,
 *    deduplikowana po siatce 0.1° (prod: 480 pustych → ~71 zapytań).
 */
class CanonizeAdvertisementRegions extends Command
{
    protected $signature = 'region:canonize
                            {--apply : Zapisz zmiany (bez tej flagi tylko raport)}
                            {--geocode : Uzupełnij puste `region` z lat/lng przez Nominatim}
                            {--limit=0 : Ogranicz liczbę geokodowanych grup (0 = bez limitu)}';

    protected $description = 'Kanonizuje advertisements.region do ASCII-id; opcjonalnie uzupełnia puste z lat/lng.';

    private const NOMINATIM_URL = 'https://nominatim.openstreetmap.org/reverse';
    private const GRID = 1; // liczba miejsc po przecinku przy dedupie współrzędnych (~11 km)

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $this->info($apply ? 'TRYB ZAPISU (--apply)' : 'DRY-RUN — nic nie zostanie zapisane');

        $changedA = $this->canonizeExisting($apply);
        $changedB = $this->option('geocode') ? $this->fillEmptyFromCoords($apply) : 0;

        $this->newLine();
        $this->info(sprintf('Podsumowanie: kanonizacja %d, uzupełnienie z lat/lng %d.', $changedA, $changedB));

        return self::SUCCESS;
    }

    /** Faza A — bez sieci: sprowadza istniejące wartości do ASCII-id. */
    private function canonizeExisting(bool $apply): int
    {
        $rows = Advertisement::query()
            ->whereNotNull('region')
            ->where('region', '!=', '')
            ->select('region', DB::raw('COUNT(*) as cnt'))
            ->groupBy('region')
            ->orderByDesc('cnt')
            ->get();

        $changed = 0;
        $this->newLine();
        $this->line('--- Faza A: kanonizacja istniejących wartości ---');

        foreach ($rows as $row) {
            $canon = RegionCanonicalizer::canonicalize($row->region);

            if ($canon === null) {
                $this->warn(sprintf('  ? "%s" (%d) — nierozpoznane, ZOSTAWIAM', $row->region, $row->cnt));
                continue;
            }
            if ($canon === $row->region) {
                continue;
            }

            $this->line(sprintf('  "%s" (%d) → "%s"', $row->region, $row->cnt, $canon));
            $changed += (int) $row->cnt;

            if ($apply) {
                DB::transaction(function () use ($row, $canon): void {
                    DB::table('advertisements')->where('region', $row->region)->update(['region' => $canon]);
                });
            }
        }

        return $changed;
    }

    /** Faza B — reverse geocoding lat/lng → województwo, dla rekordów z pustym `region`. */
    private function fillEmptyFromCoords(bool $apply): int
    {
        $ads = Advertisement::query()
            ->where(fn ($q) => $q->whereNull('region')->orWhere('region', ''))
            ->whereNotNull('latitude')->whereNotNull('longitude')
            ->where('latitude', '!=', 0)->where('longitude', '!=', 0)
            ->get(['id', 'latitude', 'longitude', 'city']);

        /** @var array<string, list<int>> $groups */
        $groups = [];
        foreach ($ads as $ad) {
            $key = round((float) $ad->latitude, self::GRID) . ',' . round((float) $ad->longitude, self::GRID);
            $groups[$key][] = (int) $ad->id;
        }

        $limit = (int) $this->option('limit');
        if ($limit > 0) {
            $groups = array_slice($groups, 0, $limit, true);
        }

        $this->newLine();
        $this->line(sprintf('--- Faza B: %d rekordów bez regionu w %d grupach (zapytań do Nominatim) ---', $ads->count(), count($groups)));

        $changed = 0;
        $bar = $this->output->createProgressBar(count($groups));

        foreach ($groups as $key => $ids) {
            [$lat, $lng] = array_map('floatval', explode(',', $key));
            $canon = $this->reverseVoivodeship($lat, $lng);
            $bar->advance();

            if ($canon === null) {
                Log::warning('region:canonize — brak województwa dla ' . $key);
                continue;
            }

            $changed += count($ids);
            if ($apply) {
                DB::transaction(function () use ($ids, $canon): void {
                    DB::table('advertisements')->whereIn('id', $ids)->update(['region' => $canon]);
                });
            }
        }

        $bar->finish();

        return $changed;
    }

    private function reverseVoivodeship(float $lat, float $lng): ?string
    {
        try {
            $resp = Http::withHeaders(['User-Agent' => 'ReklaMapImport/1.0 (kontakt@reklamap.pl)'])
                ->timeout(15)
                ->get(self::NOMINATIM_URL, [
                    'format' => 'jsonv2',
                    'lat' => $lat,
                    'lon' => $lng,
                    'zoom' => 8,          // poziom województwa
                    'accept-language' => 'pl',
                ]);

            $state = $resp->json('address.state');

            return is_string($state) ? RegionCanonicalizer::canonicalize($state) : null;
        } catch (\Throwable $e) {
            Log::warning('region:canonize — Nominatim padł: ' . $e->getMessage());

            return null;
        } finally {
            usleep(1_100_000); // Nominatim: maks. 1 zapytanie/s
        }
    }
}
