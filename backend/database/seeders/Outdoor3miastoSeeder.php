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
            $deleted = Advertisement::where('owner_email', self::OWNER_EMAIL)->delete();
            if ($deleted) {
                $this->command->info("Usunięto $deleted wcześniej zaimportowanych nośników Outdoor 3miasto.");
            }

            foreach ($records as $rec) {
                unset($rec['ref']); // pole pomocnicze, nie kolumna

                $ad = Advertisement::create($rec);
                $ad->slug = Str::slug($ad->title) . '-' . $ad->id;
                $ad->save();
            }

            $this->command->info(sprintf('Zaimportowano %d nośników Outdoor 3miasto.', count($records)));
        });
    }
}
