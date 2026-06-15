<?php

namespace Database\Seeders;

use App\Models\Advertisement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Import nośników agencji reklama.ai (411 szt.).
 *
 * Dane: seeders/data/reklama_ai.json — eksport z gotowymi (zgeokodowanymi) rekordami
 * i lokalnymi ścieżkami zdjęć (advertisements/reklama-ai/...). Zdjęcia muszą już być
 * w storage (rsync z lokalnego albo `php artisan reklama-ai:fetch-images`).
 * Seeder idempotentny: czyści wcześniejszy import tego operatora (po owner_email).
 *
 * Uruchom: php artisan db:seed --class=ReklamaAiSeeder
 */
class ReklamaAiSeeder extends Seeder
{
    private const OWNER_EMAIL = 'Ewelina@reklama.ai';

    public function run(): void
    {
        $path = database_path('seeders/data/reklama_ai.json');
        if (! is_file($path)) {
            $this->command->error("Brak pliku $path.");
            return;
        }

        $records = json_decode(file_get_contents($path), true);
        if (! is_array($records) || $records === []) {
            $this->command->error('reklama_ai.json pusty lub niepoprawny.');
            return;
        }

        DB::transaction(function () use ($records) {
            $deleted = Advertisement::where('owner_email', self::OWNER_EMAIL)->delete();
            if ($deleted) {
                $this->command->info("Usunięto $deleted wcześniej zaimportowanych nośników reklama.ai.");
            }

            $counts = [];
            foreach ($records as $rec) {
                $ad = Advertisement::create($rec);
                $ad->slug = Str::slug($ad->title) . '-' . $ad->id;
                $ad->save();
                $counts[$rec['status']] = ($counts[$rec['status']] ?? 0) + 1;
            }

            $this->command->info(sprintf(
                'Zaimportowano %d nośników reklama.ai (%s).',
                count($records),
                collect($counts)->map(fn ($n, $s) => "$s=$n")->implode(', ')
            ));
        });
    }
}
