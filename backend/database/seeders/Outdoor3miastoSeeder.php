<?php

namespace Database\Seeders;

use App\Models\Advertisement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Import nośników agencji Outdoor 3miasto z wypełnionego szablonu Excel.
 *
 * Dane przygotowuje skrypt scripts/import_outdoor3miasto.py → outdoor3miasto.json
 * (mapuje etykiety PL na kanoniczne wartości, kopiuje zdjęcia jpg+webp do storage).
 * Seeder idempotentny: czyści wcześniejszy import tego operatora (po owner_email).
 *
 * Uruchom: php artisan db:seed --class=Outdoor3miastoSeeder
 */
class Outdoor3miastoSeeder extends Seeder
{
    private const OWNER_EMAIL = 'koordynator@outdoor3miasto.com';

    public function run(): void
    {
        $path = database_path('seeders/data/outdoor3miasto.json');
        if (! is_file($path)) {
            $this->command->error("Brak pliku $path — uruchom najpierw: python3 scripts/import_outdoor3miasto.py");
            return;
        }

        $records = json_decode(file_get_contents($path), true);
        if (! is_array($records) || $records === []) {
            $this->command->error('outdoor3miasto.json pusty lub niepoprawny.');
            return;
        }

        DB::transaction(function () use ($records) {
            // updateOrCreate W MIEJSCU (klucz: owner_email + title) — NIE kasujemy i nie
            // tworzymy od nowa, bo to przedatowuje created_at („Dodano dziś"), zmienia id
            // (→ zmiana URL `slug-{id}`) i zeruje statystyki. Aktualizacja zachowuje id,
            // created_at, statystyki i URL istniejących nośników.
            $keepTitles = [];
            foreach ($records as $rec) {
                unset($rec['ref']); // pole pomocnicze, nie kolumna
                $keepTitles[] = $rec['title'];

                $ad = Advertisement::updateOrCreate(
                    ['owner_email' => self::OWNER_EMAIL, 'title' => $rec['title']],
                    $rec
                );

                // Slug ustawiamy tylko dla NOWYCH (istniejące zachowują swój → stały URL).
                if (empty($ad->slug)) {
                    $ad->slug = Str::slug($ad->title) . '-' . $ad->id;
                    $ad->save();
                }
            }

            // Usuń tylko te nośniki operatora, których NIE ma już w źródle (reszta nietknięta).
            $removed = Advertisement::where('owner_email', self::OWNER_EMAIL)
                ->whereNotIn('title', $keepTitles)
                ->delete();

            $this->command->info(sprintf(
                'Zsynchronizowano %d nośników Outdoor 3miasto (zaktualizowano w miejscu, usunięto %d nieobecnych).',
                count($records),
                $removed
            ));
        });
    }
}
