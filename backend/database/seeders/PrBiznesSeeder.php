<?php

namespace Database\Seeders;

use App\Models\Advertisement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Import billboardów agencji PR Biznes (Olsztyn).
 *
 * Dane przygotowuje scripts/import_prbiznes.py → prbiznes.json (arkusz ODS z pełnymi
 * danymi + zdjęcia PNG/wycięte z kart PDF, kopiowane do storage jako jpg+webp).
 * 3 lokalizacje (OLS 006, OLS 007, OLS 306a) świadomie pominięte w źródle — brak ceny
 * w arkuszu, czekają na dopytanie agencji.
 * Seeder idempotentny: synchronizuje wg owner_email (jak Outdoor3miastoSeeder).
 *
 * Uruchom: php artisan db:seed --class=PrBiznesSeeder
 */
class PrBiznesSeeder extends Seeder
{
    private const OWNER_EMAIL = 'biuro@prbiznes.pl';

    public function run(): void
    {
        $path = database_path('seeders/data/prbiznes.json');
        if (! is_file($path)) {
            $this->command->error("Brak pliku $path — uruchom najpierw: python3 scripts/import_prbiznes.py");
            return;
        }

        $records = json_decode(file_get_contents($path), true);
        if (! is_array($records) || $records === []) {
            $this->command->error('prbiznes.json pusty lub niepoprawny.');
            return;
        }

        DB::transaction(function () use ($records) {
            // updateOrCreate W MIEJSCU (klucz: owner_email + title) — NIE kasujemy i nie
            // tworzymy od nowa, żeby nie przedatować created_at i nie zmieniać id/URL.
            $keepTitles = [];
            foreach ($records as $rec) {
                unset($rec['ref']); // pole pomocnicze, nie kolumna
                $keepTitles[] = $rec['title'];

                $ad = Advertisement::updateOrCreate(
                    ['owner_email' => self::OWNER_EMAIL, 'title' => $rec['title']],
                    $rec
                );

                // slugifyTitle (nie Str::slug) — normalizuje kropkę dziesiętną w wymiarach
                // ("11.70x5.00 m" -> "11-70-x-5-00-m"), inaczej zlewa się w nieczytelny ciąg.
                if (empty($ad->slug)) {
                    $ad->slug = Advertisement::slugifyTitle($ad->title) . '-' . $ad->id;
                    $ad->save();
                }
            }

            $removed = Advertisement::where('owner_email', self::OWNER_EMAIL)
                ->whereNotIn('title', $keepTitles)
                ->delete();

            $this->command->info(sprintf(
                'Zsynchronizowano %d nośników PR Biznes (zaktualizowano w miejscu, usunięto %d nieobecnych).',
                count($records),
                $removed
            ));
        });
    }
}
