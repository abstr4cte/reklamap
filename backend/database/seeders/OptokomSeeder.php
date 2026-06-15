<?php

namespace Database\Seeders;

use App\Models\Advertisement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Import nośników agencji Optokom z arkusza "LISTA LOKALIZACJI 2026".
 *
 * Dane przygotowuje skrypt scripts/import_optokom.py → optokom.json.
 * Seeder jest idempotentny: czyści wcześniejszy import tego operatora
 * (po owner_email) przed ponownym wstawieniem.
 *
 * Uruchom: php artisan db:seed --class=OptokomSeeder
 */
class OptokomSeeder extends Seeder
{
    private const OWNER_EMAIL = 'biuro@optokom.pl';

    public function run(): void
    {
        $path = database_path('seeders/data/optokom.json');
        if (!is_file($path)) {
            $this->command->error("Brak pliku $path — uruchom najpierw: python3 scripts/import_optokom.py");
            return;
        }

        $records = json_decode(file_get_contents($path), true);
        if (!is_array($records) || $records === []) {
            $this->command->error('optokom.json pusty lub niepoprawny.');
            return;
        }

        DB::transaction(function () use ($records) {
            // Idempotencja — usuń poprzedni import tego operatora.
            $deleted = Advertisement::where('owner_email', self::OWNER_EMAIL)->delete();
            if ($deleted) {
                $this->command->info("Usunięto $deleted wcześniej zaimportowanych nośników Optokom.");
            }

            $counts = ['active' => 0, 'soon_available' => 0, 'reserved' => 0, 'draft' => 0];

            foreach ($records as $rec) {
                unset($rec['ref']); // pole pomocnicze, nie kolumna

                $ad = Advertisement::create($rec);
                $ad->slug = Advertisement::slugifyTitle($ad->title) . '-' . $ad->id;
                $ad->save();

                $counts[$rec['status']] = ($counts[$rec['status']] ?? 0) + 1;
            }

            $this->command->info(sprintf(
                'Zaimportowano %d nośników Optokom: active=%d, soon_available=%d, reserved=%d, draft=%d.',
                count($records),
                $counts['active'],
                $counts['soon_available'],
                $counts['reserved'],
                $counts['draft']
            ));
        });
    }
}
