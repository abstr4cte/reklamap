<?php

use App\Models\Advertisement;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfill slugów ogłoszeń po naprawie generowania (Advertisement::slugifyTitle).
 *
 * Tło: import Optokom (2026-06) utworzył nośniki z tytułami zawierającymi wymiary
 * („Billboard 5.04×2.38 m – …"), które stary Str::slug zlewał w nieczytelny ciąg
 * („billboard-504238-m-…"). Nowy helper daje „billboard-5-04-x-2-38-m-…".
 *
 * Przelicza kolumnę `slug` dla rekordów, których tytuł zmienia slug (czyli praktycznie
 * tylko nośniki z wymiarami w tytule). Aktualizuje WYŁĄCZNIE kolumnę `slug` — NIE rusza
 * `updated_at`, żeby nie odświeżyć fałszywie `lastmod` całej sitemapy. Resolucja detalu
 * jest po numerycznym `{id}`, więc stare URL-e nadal działają (200); canonical/sitemap
 * wskazują nowy, czytelny slug — Google skonsoliduje sam (te nośniki w większości nie są
 * jeszcze zaindeksowane).
 */
return new class extends Migration
{
    public function up(): void
    {
        Advertisement::query()
            ->select('id', 'title', 'slug')
            ->chunkById(200, function ($ads) {
                foreach ($ads as $ad) {
                    $newSlug = Advertisement::slugifyTitle($ad->title) . '-' . $ad->id;

                    if ($newSlug !== $ad->slug) {
                        DB::table('advertisements')
                            ->where('id', $ad->id)
                            ->update(['slug' => $newSlug]);
                    }
                }
            });
    }

    public function down(): void
    {
        // Brak rollbacku: stary slug był zepsuty (wymiary zlane w „504238"),
        // nie ma sensu go odtwarzać. Slug jest deterministyczny z tytułu.
    }
};
