<?php

namespace Database\Seeders;

use App\Models\Advertisement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Import nośników agencji Big Group Sp. z o.o. Sk. z cenników Excel 2026.
 *
 * Dane przygotowuje skrypt scripts/import_biggroup.py → biggroup.json
 * (parsuje 6 cenników: Autostrada A2, Wrocław, Poznań, Września, Gniezno, Kościan;
 * mapuje typy po oględzinach zdjęć: wielki format → wall, A2 → billboard/highway,
 * backlight/citylight → citylight/indoor; pobiera og:image ze stron produktowych
 * i zapisuje jpg+webp do storage).
 *
 * Seeder idempotentny: czyści wcześniejszy import tego operatora (po owner_email).
 *
 * Uruchom: php artisan db:seed --class=BigGroupSeeder
 */
class BigGroupSeeder extends Seeder
{
    private const OWNER_EMAIL = 'info@biggroup.pl';

    public function run(): void
    {
        $path = database_path('seeders/data/biggroup.json');
        if (! is_file($path)) {
            $this->command->error("Brak pliku $path — uruchom najpierw: python3 scripts/import_biggroup.py");
            return;
        }

        $records = json_decode(file_get_contents($path), true);
        if (! is_array($records) || $records === []) {
            $this->command->error('biggroup.json pusty lub niepoprawny.');
            return;
        }

        DB::transaction(function () use ($records) {
            $deleted = Advertisement::where('owner_email', self::OWNER_EMAIL)->delete();
            if ($deleted) {
                $this->command->info("Usunięto $deleted wcześniej zaimportowanych nośników Big Group.");
            }

            foreach ($records as $rec) {
                $ad = Advertisement::create($rec);
                // slugifyTitle (nie Str::slug) — musi być zgodny ze slugify.ts we froncie,
                // który generuje linki/canonical z tytułu; inaczej sitemap rozjedzie się z URL-ami.
                $ad->slug = Advertisement::slugifyTitle($ad->title) . '-' . $ad->id;
                $ad->save();
            }

            $this->command->info(sprintf('Zaimportowano %d nośników Big Group.', count($records)));
        });
    }
}
