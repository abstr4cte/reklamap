<?php

namespace App\Console\Commands;

use App\Models\Advertisement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportBrokersMedia extends Command
{
    /**
     * @var string
     */
    protected $signature = 'import:brokersmedia
        {--csv= : Ścieżka do CSV (domyślnie reklamap-os/status/brokersmedia_nosniki.csv).}
        {--purge : Najpierw usuń wcześniejszy import tego operatora (po owner_email).}
        {--dry-run : Nic nie zapisuje — pokazuje, co zostałoby utworzone.}';

    /**
     * @var string
     */
    protected $description = 'Importuje portfolio nośników BrokersMedia z CSV do bazy (Oświęcim/Zator/Andrychów/Kęty/Brzeszcze/Libiąż).';

    private const OWNER_EMAIL = 'biuro@brokersmedia.pl';
    private const PHONE = '603343171';

    /** Ulice sugerujące ruch tranzytowy (drogi krajowe/wojewódzkie) — heurystyka jak w import:reklama-ai. */
    private const HEAVY_TRAFFIC_PATTERN = '/obwodnic|trasa|DK[- ]?\d|krajow|wojewódzk|ekspresow|nr ?\d{2,3}/i';

    public function handle(): int
    {
        $csv = $this->option('csv') ?: base_path('../reklamap-os/status/brokersmedia_nosniki.csv');
        if (! is_file($csv)) {
            $this->error("Brak pliku CSV: {$csv}");
            return self::FAILURE;
        }

        $rows = $this->readCsv($csv);
        $this->info(sprintf('Wczytano %d nośników z CSV.', count($rows)));

        $dryRun = (bool) $this->option('dry-run');

        if ($this->option('purge') && ! $dryRun) {
            $deleted = Advertisement::where('owner_email', self::OWNER_EMAIL)->delete();
            $this->warn("Usunięto wcześniejszy import: {$deleted} rekordów.");
        }

        $stats = ['billboard' => 0, 'mobile' => 0, 'skip' => 0];
        $created = [];

        $work = function () use ($rows, $dryRun, &$stats, &$created): void {
            foreach ($rows as $r) {
                $data = $this->mapRow($r);
                if ($data === null) {
                    $stats['skip']++;
                    continue;
                }
                $stats[$data['type']] = ($stats[$data['type']] ?? 0) + 1;

                if ($dryRun) {
                    continue;
                }

                $ad = Advertisement::create($data);
                $ad->slug = Advertisement::slugifyTitle($ad->title) . '-' . $ad->id;
                $ad->save();
                $created[] = $ad->id;
            }
        };

        if ($dryRun) {
            $work();
        } else {
            DB::transaction($work);
        }

        $this->newLine();
        $this->table(
            ['typ', 'liczba'],
            [
                ['billboard', $stats['billboard']],
                ['mobile (reklama mobilna / na aucie)', $stats['mobile']],
                ['pominięte (brak wymaganych danych)', $stats['skip']],
            ]
        );
        $this->info($dryRun
            ? 'PRÓBA — nic nie zapisano.'
            : sprintf('Import zakończony. Utworzono %d ogłoszeń.', count($created)));

        return self::SUCCESS;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function readCsv(string $path): array
    {
        $fh = fopen($path, 'r');
        $header = fgetcsv($fh, 0, ';');
        // Usuń BOM z pierwszej kolumny nagłówka, jeśli obecny (plik zapisany jako UTF-8 z BOM).
        if (isset($header[0])) {
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        }
        $rows = [];
        while (($line = fgetcsv($fh, 0, ';')) !== false) {
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
        $city = trim($r['miasto_osm']) ?: trim($r['miasto_excel']);
        $street = trim($r['ulica']);
        $lat = (float) $r['lat'];
        $lng = (float) $r['lng'];
        $price = (float) $r['cena_mc'];
        $type = trim($r['typ_reklamap']); // 'billboard' | 'mobile' — już zmapowane w CSV

        if ($city === '' || $lat === 0.0 || $lng === 0.0 || $price <= 0 || ! in_array($type, ['billboard', 'mobile'], true)) {
            return null;
        }

        $width = $r['szer_m'] !== '' ? (float) $r['szer_m'] : null;
        $height = $r['wys_m'] !== '' ? (float) $r['wys_m'] : null;

        $typeLabel = $type === 'mobile' ? 'Reklama mobilna' : 'Billboard';

        // Konstrukcja/strona rozróżniają kilka nośników pod tym samym adresem (różne ściany/strony
        // tej samej konstrukcji) — dopisujemy do tytułu, żeby się nie dublowały wizualnie.
        $suffix = trim(($r['konstrukcja'] ?: '') . ($r['strona'] ? '/' . $r['strona'] : ''));
        $title = trim("{$typeLabel} {$city} — {$street}" . ($suffix ? " {$suffix}" : ''));
        $title = Str::limit($title, 120, '');

        $orientation = ($width && $height)
            ? ($width >= $height ? 'horizontal' : 'vertical')
            : 'horizontal';

        $heavyTraffic = (bool) preg_match(self::HEAVY_TRAFFIC_PATTERN, $street);

        $dimsText = $width && $height
            ? sprintf('%s×%s m', rtrim(rtrim((string) $width, '0'), '.'), rtrim(rtrim((string) $height, '0'), '.'))
            : trim($r['wymiar']);

        $desc = sprintf(
            '%s%s w lokalizacji: %s, %s. Nośnik z portfolio agencji BrokersMedia. Stawka %s zł/mc netto.',
            $typeLabel,
            $dimsText ? " o wymiarach {$dimsText}" : '',
            $street,
            $city,
            (int) $price
        );

        return [
            'title' => $title,
            'type' => $type,
            'variant' => $type === 'mobile' ? 'trailer' : 'standard',
            'location' => $street,
            'city' => $city,
            'latitude' => $lat,
            'longitude' => $lng,
            'description' => $desc,
            'price' => $price,
            'price_unit' => 'month',
            'width' => $width,
            'height' => $height,
            'owner_email' => self::OWNER_EMAIL,
            'phone' => self::PHONE,
            'orientation' => $orientation,
            'traffic_intensity' => $heavyTraffic ? 'high' : null,
            'offer_type' => 'agency',
            'road_class' => $type === 'billboard' ? ($heavyTraffic ? 'regional' : 'urban') : null,
            'has_backlight' => trim($r['oswietlenie']) === 'tak',
            'price_negotiable' => false,
            'has_vat_invoice' => true,
            'price_includes_print' => false,
            'price_includes_mounting' => false,
            'graphic_design_help' => false,
            'has_image' => false,
            'status' => 'active',
            'is_active' => true,
        ];
    }
}
