<?php

namespace Database\Seeders;

use App\Models\Advertisement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Import nośników agencji pdesign (P Design, Koszalin/Kołobrzeg/Mścice) — 80 billboardów 12 m².
 *
 * Dane: seeders/data/pdesign.json (scripts/import_pdesign.py — adresy z grafik OCR-em,
 * geokodowanie street+landmark). Zdjęcia: advertisements/pdesign/ (muszą być w storage).
 * Idempotentny: czyści wcześniejszy import tego operatora (po owner_email).
 *
 * Uruchom: php artisan db:seed --class=PdesignSeeder
 */
class PdesignSeeder extends Seeder
{
    private const OWNER_EMAIL = 'biuro@pdesign.com.pl';

    public function run(): void
    {
        $path = database_path('seeders/data/pdesign.json');
        if (! is_file($path)) {
            $this->command->error("Brak $path.");
            return;
        }

        $records = json_decode(file_get_contents($path), true);
        if (! is_array($records) || $records === []) {
            $this->command->error('pdesign.json pusty lub niepoprawny.');
            return;
        }

        DB::transaction(function () use ($records) {
            $deleted = Advertisement::where('owner_email', self::OWNER_EMAIL)->delete();
            if ($deleted) {
                $this->command->info("Usunięto $deleted wcześniej zaimportowanych nośników pdesign.");
            }

            foreach ($records as $rec) {
                $ad = Advertisement::create($rec);
                $ad->slug = Str::slug($ad->title) . '-' . $ad->id;
                $ad->save();
            }

            $this->command->info(sprintf('Zaimportowano %d nośników pdesign.', count($records)));
        });
    }
}
