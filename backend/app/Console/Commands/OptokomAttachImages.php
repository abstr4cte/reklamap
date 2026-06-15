<?php

namespace App\Console\Commands;

use App\Models\Advertisement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Podpina realne zdjęcia Optokom do ISTNIEJĄCYCH ogłoszeń — UPDATE in-place,
 * BEZ delete/recreate. Zachowuje ID, statystyki (advertisement_daily_stats),
 * slug/URL-e i ewentualne ręczne edycje. Bezpieczne dla SEO.
 *
 * Powiązanie: OptokomSeeder wstawia rekordy w kolejności z optokom.json, więc
 * i-ty rekord w pliku = i-ty najstarszy rekord Optokomu w bazie (ORDER BY id ASC).
 * Przed zapisem komenda WALIDUJE to wyrównanie (tytuł+miasto na każdej pozycji);
 * przy rozjeździe przerywa, nic nie zmieniając.
 */
class OptokomAttachImages extends Command
{
    protected $signature = 'optokom:attach-images
        {--dry-run : Tylko sprawdza wyrównanie i pokazuje, co zostałoby zmienione.}
        {--force : Pomija przerwanie przy drobnych rozjazdach wyrównania (NIEZALECANE).}';

    protected $description = 'Podpina zdjęcia Optokom do istniejących ogłoszeń (UPDATE po pozycji, bez delete — bezpieczne dla ID/statystyk/URL).';

    private const OWNER_EMAIL = 'biuro@optokom.pl';
    private const PLACEHOLDER = 'advertisements/optokom-placeholder.jpg';

    public function handle(): int
    {
        $path = database_path('seeders/data/optokom.json');
        if (! is_file($path)) {
            $this->error("Brak $path — uruchom najpierw scripts/fetch_optokom_images.py.");
            return self::FAILURE;
        }

        $records = json_decode(file_get_contents($path), true);
        $ads = Advertisement::where('owner_email', self::OWNER_EMAIL)
            ->orderBy('id')->get();

        $this->info(sprintf('optokom.json: %d rekordów | baza: %d ogłoszeń Optokom', count($records), $ads->count()));

        // 1. Liczność musi się zgadzać
        if (count($records) !== $ads->count()) {
            $this->error("Różna liczba rekordów ({$ads->count()} w bazie vs " . count($records) . " w pliku) — przerwane. Wyrównanie pozycyjne niepewne.");
            return self::FAILURE;
        }

        // 2. Walidacja wyrównania pozycyjnego (tytuł + miasto)
        $misaligned = [];
        foreach ($records as $i => $r) {
            $ad = $ads[$i];
            if (trim((string) $ad->title) !== trim((string) ($r['title'] ?? ''))
                || trim((string) $ad->city) !== trim((string) ($r['city'] ?? ''))) {
                $misaligned[] = "  poz. {$i}: baza[{$ad->id}] „{$ad->title} / {$ad->city}\" ≠ plik „{$r['title']} / {$r['city']}\"";
            }
        }

        if ($misaligned !== []) {
            $this->error(sprintf('Wyrównanie nie pasuje na %d pozycjach:', count($misaligned)));
            $this->line(implode("\n", array_slice($misaligned, 0, 15)));
            if (! $this->option('force')) {
                $this->error('Przerwane (użyj --force tylko jeśli wiesz, co robisz). Bezpieczniejsza alternatywa: reseed.');
                return self::FAILURE;
            }
            $this->warn('--force: kontynuuję mimo rozjazdu.');
        } else {
            $this->info('✓ Wyrównanie pozycyjne potwierdzone na wszystkich ' . count($records) . ' pozycjach.');
        }

        // 3. Update tylko pól zdjęcia, tylko tam gdzie jest realne foto
        $dryRun = (bool) $this->option('dry-run');
        $updated = $skipped = 0;

        $apply = function () use ($records, $ads, $dryRun, &$updated, &$skipped): void {
            foreach ($records as $i => $r) {
                $img = $r['image_url'] ?? null;
                if (! $img || $img === self::PLACEHOLDER) {
                    $skipped++;
                    continue;
                }
                if (! $dryRun) {
                    $ads[$i]->forceFill([
                        'image_url' => $img,
                        'images' => $r['images'] ?? [$img],
                        'has_image' => true,
                    ])->save();
                }
                $updated++;
            }
        };

        $dryRun ? $apply() : DB::transaction($apply);

        $this->newLine();
        $this->table(['metryka', 'liczba'], [
            ['zaktualizowane (realne foto)', $updated],
            ['pominięte (placeholder/brak)', $skipped],
        ]);
        $this->info($dryRun
            ? 'PRÓBA — nic nie zapisano. URL-e/ID/statystyki nietknięte.'
            : 'Gotowe. Zmienione tylko zdjęcia; ID, slug/URL-e i statystyki bez zmian.');

        return self::SUCCESS;
    }
}
