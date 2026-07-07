<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Tripwire deindeksu (audyt SEO 2026-07-07, finding #10). Pobiera reprezentatywne URL-e PROD
 * jako Googlebot i sprawdza niezmienniki, których złamanie = cichy deindeks: strona ma być
 * `index`, mieć zaszyty stan (__INITIAL_STATE__) i realną treść, bez fałszywego empty-state.
 * Wykrywa: zły deploy (noindex/pusty szkielet), regresję prerenderu/seedu, padnięcie renderu —
 * ZANIM wyjdzie to jako spadek ruchu w GSC (dziś wykrywalne dopiero po tygodniach).
 *
 * Cron (Hostido), np.: 0 7 * * * cd <repo>/backend && php artisan seo:tripwire
 * albo przez scheduler (routes/console.php), jeśli `schedule:run` jest w cronie.
 */
class SeoTripwire extends Command
{
    protected $signature = 'seo:tripwire {--email= : Adres do alertu (domyślnie mail.from.address)}';

    protected $description = 'Sprawdza na PROD, czy reprezentatywne strony są index + mają seed + treść (tripwire deindeksu)';

    private const UA = 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';

    private const MIN_TEXT = 500;

    public function handle(): int
    {
        $base = rtrim((string) config('app.frontend_url', 'https://reklamap.pl'), '/');
        $failures = [];

        foreach ($this->buildChecks($base) as $check) {
            $problems = $this->inspect($check);
            if ($problems !== []) {
                $failures[] = $check['url'] . ': ' . implode('; ', $problems);
                $this->error('✗ ' . $check['url'] . ' — ' . implode('; ', $problems));
            } else {
                $this->info('✓ ' . $check['url']);
            }
        }

        if ($failures !== []) {
            $summary = implode("\n", $failures);
            Log::critical("SEO tripwire: problemy indeksacji na PROD:\n" . $summary);
            $this->sendAlert($summary);

            return self::FAILURE;
        }

        $this->info('SEO tripwire OK — wszystkie strony index + seed + treść.');

        return self::SUCCESS;
    }

    /**
     * Próbkuje URL-e z PROD-SITEMAPY — to zbiór, który MA być index + prerenderowany. Dzięki temu
     * testujemy dokładnie to, co sitemap reklamuje, i NIE alarmujemy fałszywie na świeżym nośniku
     * jeszcze nieobjętym build-time prerenderem (którego URL byłby dopiero po następnym deployu).
     * Sitemap czytamy z PROD (statyczny dist), więc działa też uruchamiane lokalnie.
     *
     * @return array<int, array{url: string, needState: bool, needIndex: bool}>
     */
    private function buildChecks(string $base): array
    {
        $checks = [
            ['url' => $base . '/', 'needState' => true, 'needIndex' => true],
        ];

        $locs = [];
        try {
            $sitemap = Http::withHeaders(['User-Agent' => self::UA])->timeout(20)->get($base . '/sitemap.xml')->body();
            if (preg_match_all('/<loc>([^<]+)<\/loc>/', $sitemap, $m)) {
                $locs = array_map(fn ($l) => html_entity_decode($l, ENT_QUOTES | ENT_HTML5), $m[1]);
            }
        } catch (\Throwable $e) {
            // sitemap niedostępny — to samo w sobie alarm; złapie to niska liczba checków niżej.
        }

        // Po jednym reprezentancie każdej klasy renderu (pierwszy pasujący = deterministyczny).
        $pick = function (string $regex) use ($locs): ?string {
            foreach ($locs as $loc) {
                if (preg_match($regex, $loc)) {
                    return $loc;
                }
            }

            return null;
        };

        $samples = [
            $pick('#/powierzchnie-reklamowe/[^/]+/[^/]+$#'), // combo typ×miasto (render kategorii+miasta)
            $pick('#/powierzchnie-reklamowe/[^/]+$#'),       // kategoria typ/miasto
            $pick('#/powierzchnia-reklamowa/#'),             // leaf (ogłoszenie)
            $pick('#/blog/[^/]+/[^/]+$#'),                   // artykuł bloga (E-E-A-T, seed #1)
        ];
        foreach (array_filter($samples) as $url) {
            $checks[] = ['url' => $url, 'needState' => true, 'needIndex' => true];
        }

        // Sitemap pusty/niedostępny → dołóż statyczny listing, żeby check w ogóle coś sprawdził.
        if (count($checks) < 3) {
            $checks[] = ['url' => $base . '/powierzchnie-reklamowe', 'needState' => true, 'needIndex' => true];
        }

        return $checks;
    }

    /**
     * @param array{url: string, needState: bool, needIndex: bool} $check
     * @return array<int, string> lista problemów (pusta = OK)
     */
    private function inspect(array $check): array
    {
        try {
            $res = Http::withHeaders(['User-Agent' => self::UA])->timeout(20)->get($check['url']);
        } catch (\Throwable $e) {
            return ['fetch padł: ' . $e->getMessage()];
        }

        if ($res->status() !== 200) {
            return ['HTTP ' . $res->status()];
        }

        $html = $res->body();
        $problems = [];

        if ($check['needIndex'] && preg_match('/<meta[^>]+name=["\']robots["\'][^>]*noindex/i', $html)) {
            $problems[] = 'noindex (spodziewano index)';
        }
        if ($check['needState'] && ! str_contains($html, '__INITIAL_STATE__')) {
            $problems[] = 'brak __INITIAL_STATE__ (seed zgubiony → ryzyko pustego renderu WRS)';
        }
        if (preg_match('/Brak ogłoszeń dla/i', $html)) {
            $problems[] = 'fałszywy empty-state „Brak ogłoszeń dla"';
        }
        $text = trim((string) preg_replace('/\s+/', ' ', strip_tags($html)));
        if (mb_strlen($text) < self::MIN_TEXT) {
            $problems[] = 'znikoma treść (' . mb_strlen($text) . ' zn.) — możliwy szkielet SPA zamiast prerenderu';
        }

        return $problems;
    }

    private function sendAlert(string $summary): void
    {
        $to = $this->option('email') ?: config('mail.from.address');
        if (! $to) {
            $this->warn('Brak adresu alertu (mail.from.address puste) — pominięto mail, jest wpis w logu.');

            return;
        }

        try {
            Mail::raw(
                "SEO tripwire wykrył problemy indeksacji na PROD:\n\n" . $summary . "\n\nSprawdź: ostatni deploy frontu, prerender/seed, WAF api.reklamap.pl, GSC.",
                function ($m) use ($to) {
                    $m->to($to)->subject('[ReklaMap] ALERT: tripwire deindeksu');
                }
            );
            $this->info('Alert wysłany na ' . $to . '.');
        } catch (\Throwable $e) {
            $this->warn('Nie udało się wysłać alertu (jest wpis w logu): ' . $e->getMessage());
        }
    }
}
