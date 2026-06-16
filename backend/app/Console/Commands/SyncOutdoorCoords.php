<?php

namespace App\Console\Commands;

use App\Models\Advertisement;
use Illuminate\Console\Command;

/**
 * Aktualizuje współrzędne nośników Outdoor 3miasto z outdoor3miasto.json — UPDATE in-place
 * (dopasowanie po tytule), bez kasowania/zmiany ID/URL. Dla precyzyjnych pinezek od agencji.
 *
 * Uruchom: php artisan outdoor:sync-coords
 */
class SyncOutdoorCoords extends Command
{
    protected $signature = 'outdoor:sync-coords';
    protected $description = 'Aktualizuje lat/lng nośników Outdoor 3miasto z JSON (in-place, po tytule).';

    private const OWNER_EMAIL = 'koordynator@outdoor3miasto.com';

    public function handle(): int
    {
        $path = database_path('seeders/data/outdoor3miasto.json');
        if (! is_file($path)) {
            $this->error("Brak $path — zrób git pull.");
            return self::FAILURE;
        }

        $records = json_decode(file_get_contents($path), true);
        $updated = $missing = 0;

        foreach ($records as $r) {
            $n = Advertisement::where('owner_email', self::OWNER_EMAIL)
                ->where('title', $r['title'])
                ->update(['latitude' => $r['latitude'], 'longitude' => $r['longitude']]);
            $n ? $updated += $n : $missing++;
            if (! $n) {
                $this->line("  brak dopasowania: {$r['title']}");
            }
        }

        $this->info("Zaktualizowano pinezki: {$updated}" . ($missing ? " | bez dopasowania: {$missing}" : ''));
        return self::SUCCESS;
    }
}
