<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Tripwire deindeksu (SeoTripwire) — codzienny check na PROD, że reprezentatywne strony są
// index + mają seed + treść. DZIAŁA tylko, gdy `schedule:run` jest w cronie Hostido:
//   * * * * * cd <repo>/backend && php artisan schedule:run >> /dev/null 2>&1
// Alternatywnie odpal komendę wprost z crona bez schedulera:
//   0 7 * * * cd <repo>/backend && php artisan seo:tripwire >> storage/logs/tripwire.log 2>&1
Schedule::command('seo:tripwire')->dailyAt('07:00')->withoutOverlapping();
