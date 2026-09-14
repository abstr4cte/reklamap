<?php

namespace Database\Seeders;

use App\Models\Advertisement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Nośniki agencji PRS — Reklama Przemyśl (prs@reklamaprzemysl.pl).
 *
 * Dane: backend/database/seeders/data/reklamaprzemysl.json, generowane przez
 * scripts/import_reklamaprzemysl.py (strona operatora + arkusz GPS + cennik PDF).
 * Klucz naturalny: owner_email + title (tytuł zawiera numer PRS → stabilny).
 * Update w miejscu (updateOrCreate), rekordy nieobecne w JSON są usuwane.
 */
class ReklamaPrzemyslSeeder extends Seeder
{
    private const OWNER_EMAIL = 'prs@reklamaprzemysl.pl';

    public function run(): void
    {
        $path = database_path('seeders/data/reklamaprzemysl.json');
        if (! is_file($path)) {
            $this->command->error("Brak pliku $path — uruchom najpierw: python3 scripts/import_reklamaprzemysl.py");

            return;
        }

        $records = json_decode(file_get_contents($path), true);
        if (! is_array($records) || $records === []) {
            $this->command->error('reklamaprzemysl.json pusty lub niepoprawny.');

            return;
        }

        DB::transaction(function () use ($records): void {
            $keepTitles = [];
            foreach ($records as $rec) {
                unset($rec['ref']);
                $keepTitles[] = $rec['title'];

                $ad = Advertisement::updateOrCreate(
                    ['owner_email' => self::OWNER_EMAIL, 'title' => $rec['title']],
                    $rec
                );
                if (empty($ad->slug)) {
                    $ad->slug = Advertisement::slugifyTitle($ad->title).'-'.$ad->id;
                    $ad->save();
                }
            }

            $removed = Advertisement::where('owner_email', self::OWNER_EMAIL)
                ->whereNotIn('title', $keepTitles)
                ->delete();

            $this->command->info(sprintf(
                'Zsynchronizowano %d nośników PRS Reklama Przemyśl (update w miejscu, usunięto %d nieobecnych).',
                count($records),
                $removed
            ));
        });
    }
}
