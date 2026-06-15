<?php

namespace App\Console\Commands;

use App\Models\Advertisement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportReklamaAi extends Command
{
    /**
     * @var string
     */
    protected $signature = 'import:reklama-ai
        {--csv= : Ścieżka do CSV ze scrapera (domyślnie reklamap-os/status/reklama_ai_nosniki.csv).}
        {--purge : Najpierw usuń wcześniejszy import tego operatora (po owner_email).}
        {--dry-run : Nic nie zapisuje — pokazuje, co zostałoby utworzone.}
        {--no-geo : Pomiń geokodowanie (lat/lng = środek miasta z cache, brak = 0 i status draft).}';

    /**
     * @var string
     */
    protected $description = 'Importuje portfolio nośników reklama.ai z CSV do bazy (test lokalny).';

    private const OWNER_EMAIL = 'Ewelina@reklama.ai';
    private const PHONE = '602534843';
    private const CACHE = 'geocode-reklama-ai.json';

    /** nośniki niepewne cenowo → wgrywamy jako szkic (niewidoczne), domailujemy z agencją */
    private const UNCERTAIN = ['telebim1', 'telebim2', '461'];

    /** @var array<string, array{lat: float, lng: float}> */
    private array $geoCache = [];

    public function handle(): int
    {
        $csv = $this->option('csv') ?: base_path('../reklamap-os/status/reklama_ai_nosniki.csv');
        if (! is_file($csv)) {
            $this->error("Brak pliku CSV: {$csv}");
            return self::FAILURE;
        }

        $rows = $this->readCsv($csv);
        $this->info(sprintf('Wczytano %d nośników z CSV.', count($rows)));

        $this->loadGeoCache();

        $dryRun = (bool) $this->option('dry-run');

        if ($this->option('purge') && ! $dryRun) {
            $deleted = Advertisement::where('owner_email', self::OWNER_EMAIL)->delete();
            $this->warn("Usunięto wcześniejszy import: {$deleted} rekordów.");
        }

        $stats = ['active' => 0, 'reserved' => 0, 'draft' => 0, 'skip' => 0];

        $work = function () use ($rows, $dryRun, &$stats): void {
            foreach ($rows as $r) {
                $data = $this->mapRow($r);
                if ($data === null) {
                    $stats['skip']++;
                    continue;
                }
                $stats[$data['status']] = ($stats[$data['status']] ?? 0) + 1;

                if ($dryRun) {
                    continue;
                }
                Advertisement::create($data);
            }
        };

        if ($dryRun) {
            $work();
        } else {
            DB::transaction($work);
            $this->saveGeoCache();
        }

        $this->newLine();
        $this->table(
            ['status', 'liczba'],
            [
                ['active (wolne, widoczne)', $stats['active']],
                ['reserved (zajęte, oznaczone)', $stats['reserved']],
                ['draft (niepewne, ukryte)', $stats['draft']],
                ['pominięte', $stats['skip']],
            ]
        );
        $this->info($dryRun ? 'PRÓBA — nic nie zapisano.' : 'Import zakończony.');

        return self::SUCCESS;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function readCsv(string $path): array
    {
        $fh = fopen($path, 'r');
        $header = fgetcsv($fh);
        $rows = [];
        while (($line = fgetcsv($fh)) !== false) {
            $rows[] = array_combine($header, $line);
        }
        fclose($fh);
        return $rows;
    }

    /**
     * @param array<string, string> $r
     * @return array<string, mixed>|null
     */
    private function mapRow(array $r): ?array
    {
        $nr = trim($r['nr']);
        $miasto = $this->cleanCity($r['miasto']);
        $adres = trim($r['adres']) ?: trim($r['miasto']);
        $price = (float) $r['cena'];

        if ($miasto === '' || $price <= 0) {
            return null;
        }

        [$lat, $lng] = $this->geocode($miasto, $adres);
        $uncertain = in_array($nr, self::UNCERTAIN, true) || $lat === null;

        $status = $uncertain ? 'draft' : ($r['status'] === 'wolny' ? 'active' : 'reserved');

        $width = (float) $r['szerokosc_m'];
        $height = (float) $r['wysokosc_m'];
        $pow = $r['powierzchnia_m2'] ?: round($width * $height);
        $typeLabel = $r['typ_nosnika'] === 'led_screen' ? 'Ekran LED' : 'Billboard';
        $title = trim("{$typeLabel} {$miasto} — {$adres}");
        $title = Str::limit($title, 120, '');

        $heavyTraffic = (bool) preg_match('/obwodnic|trasa|DK[- ]?\d|krajow|autostrad|ekspresow|S\d|nr ?8/i', $adres);

        $desc = sprintf(
            '%s o wymiarach %s×%s m (%s m²) w lokalizacji: %s, %s. Powierzchnia reklamowa w portfolio agencji. Stawka %s zł/mc netto (cena wywoławcza, do negocjacji).',
            $typeLabel,
            rtrim(rtrim((string) $width, '0'), '.'),
            rtrim(rtrim((string) $height, '0'), '.'),
            $pow,
            $miasto,
            $adres,
            (int) $price
        );

        return [
            'title' => $title,
            'type' => $r['typ_nosnika'],
            'location' => $adres,
            'city' => $miasto,
            'latitude' => $lat ?? 0,
            'longitude' => $lng ?? 0,
            'description' => $desc,
            'price' => $price,
            'price_unit' => 'month',
            'width' => $width,
            'height' => $height,
            'owner_email' => self::OWNER_EMAIL,
            'phone' => self::PHONE,
            'orientation' => 'horizontal',
            'traffic_intensity' => $heavyTraffic ? 'high' : 'medium',
            'offer_type' => 'agency',
            'variant' => $r['typ_nosnika'] === 'billboard' ? 'standard' : null,
            'road_class' => $heavyTraffic ? 'national' : 'urban',
            'has_backlight' => $r['podswietlany'] === 'tak',
            'price_negotiable' => true,
            'has_vat_invoice' => true,
            'price_includes_print' => false,
            'price_includes_mounting' => false,
            'image_url' => $r['link_zdjecia'] ?: null,
            'has_image' => $r['ma_zdjecie'] === 'tak',
            'status' => $status,
            'is_active' => $status !== 'draft',
            'slug' => Str::slug("{$typeLabel}-{$miasto}-{$nr}"),
        ];
    }

    /**
     * @return array{0: float|null, 1: float|null}
     */
    private function geocode(string $city, string $address): array
    {
        if ($this->option('no-geo')) {
            $hit = $this->geoCache[$this->key($city)] ?? null;
            return $hit ? [$hit['lat'], $hit['lng']] : [null, null];
        }

        $key = $this->key($city);
        if (! isset($this->geoCache[$key])) {
            try {
                $resp = Http::withHeaders(['User-Agent' => 'ReklaMapImport/1.0 (kontakt@reklamap.pl)'])
                    ->get('https://nominatim.openstreetmap.org/search', [
                        'q' => "{$city}, Polska",
                        'format' => 'json',
                        'limit' => 1,
                    ]);
                $j = $resp->json();
                if (! empty($j[0]['lat'])) {
                    $this->geoCache[$key] = ['lat' => (float) $j[0]['lat'], 'lng' => (float) $j[0]['lon']];
                    $this->line("  geo: {$city} → {$j[0]['lat']}, {$j[0]['lon']}");
                }
            } catch (\Throwable $e) {
                $this->warn("  geo błąd dla {$city}: {$e->getMessage()}");
            }
            usleep(1_100_000); // Nominatim: maks. 1 zapytanie/s
        }

        $base = $this->geoCache[$key] ?? null;
        if (! $base) {
            return [null, null];
        }

        // drobny, deterministyczny offset per nośnik, żeby pinezki w tym samym mieście się nie nakładały
        $h = crc32($address);
        $jLat = (($h % 1000) / 1000 - 0.5) * 0.012;
        $jLng = ((intdiv($h, 1000) % 1000) / 1000 - 0.5) * 0.012;

        return [round($base['lat'] + $jLat, 8), round($base['lng'] + $jLng, 8)];
    }

    private function key(string $city): string
    {
        return Str::lower(trim($city));
    }

    /** Czyści nazwę miasta z dopisków: „Krosnowice (k. Kłodzka)" → „Krosnowice", „Kłodzko, ul X" → „Kłodzko". */
    private function cleanCity(string $city): string
    {
        $city = preg_split('/[,(]/u', $city)[0];           // ucięcie po przecinku / nawiasie
        $city = preg_split('/\s+-\s+/u', $city)[0];        // ucięcie po " - " (ze spacjami; "Lądek-Zdrój" zostaje)
        return trim($city);
    }

    private function loadGeoCache(): void
    {
        if (Storage::exists(self::CACHE)) {
            $this->geoCache = json_decode(Storage::get(self::CACHE), true) ?: [];
        }
    }

    private function saveGeoCache(): void
    {
        Storage::put(self::CACHE, json_encode($this->geoCache, JSON_PRETTY_PRINT));
    }
}
