<?php

namespace App\Console\Commands;

use App\Models\Advertisement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Laravel\Facades\Image;

class FetchReklamaAiImages extends Command
{
    /**
     * @var string
     */
    protected $signature = 'reklama-ai:fetch-images
        {--limit=0 : Maks. liczba zdjęć do pobrania (0 = wszystkie). Do testu na próbce.}
        {--force : Pobierz ponownie nawet te, które już mają lokalny image_url.}';

    /**
     * @var string
     */
    protected $description = 'Pobiera zdjęcia nośników reklama.ai do lokalnego storage (jpg + webp) i podmienia image_url.';

    private const OWNER_EMAIL = 'Ewelina@reklama.ai';
    private const SUBDIR = 'advertisements/reklama-ai';

    public function handle(): int
    {
        $dir = storage_path('app/public/' . self::SUBDIR);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $query = Advertisement::where('owner_email', self::OWNER_EMAIL)
            ->whereNotNull('image_url')
            ->where('image_url', 'like', 'http%'); // tylko jeszcze nie-zlokalizowane (hotlink)

        if ($this->option('force')) {
            $query = Advertisement::where('owner_email', self::OWNER_EMAIL)->whereNotNull('image_url');
        }

        $limit = (int) $this->option('limit');
        if ($limit > 0) {
            $query->limit($limit);
        }

        $ads = $query->get();
        $this->info("Do pobrania: {$ads->count()} zdjęć.");

        $ok = $fail = 0;
        $bar = $this->output->createProgressBar($ads->count());
        $bar->start();

        foreach ($ads as $ad) {
            $src = $ad->image_url;
            // jeśli --force trafił na już lokalny, odtwórz URL źródłowy z pola? — pomijamy, gdy nie http
            if (! str_starts_with($src, 'http')) {
                $bar->advance();
                continue;
            }

            $base = pathinfo(parse_url($src, PHP_URL_PATH), PATHINFO_FILENAME); // np. 021_01_01
            $jpgRel = self::SUBDIR . "/{$base}.jpg";
            $webpRel = self::SUBDIR . "/{$base}.webp";

            try {
                $resp = Http::withHeaders(['User-Agent' => 'ReklaMapImport/1.0 (kontakt@reklamap.pl)'])
                    ->timeout(30)->get($src);

                if (! $resp->successful() || $resp->body() === '') {
                    throw new \RuntimeException("HTTP {$resp->status()}");
                }

                $img = Image::read($resp->body());
                // normalizacja: max szer. 1600px (oryginały bywają duże), zachowujemy proporcje
                if ($img->width() > 1600) {
                    $img->scaleDown(width: 1600);
                }
                $img->toJpeg(85)->save(storage_path('app/public/' . $jpgRel));
                $img->toWebp(85)->save(storage_path('app/public/' . $webpRel));

                $ad->image_url = $jpgRel;
                $ad->has_image = true;
                $ad->save();
                $ok++;
            } catch (\Throwable $e) {
                $fail++;
                Log::warning('reklama.ai image fetch failed', ['src' => $src, 'err' => $e->getMessage()]);
                $this->newLine();
                $this->warn("  błąd: {$src} — {$e->getMessage()}");
            }

            $bar->advance();
            usleep(150_000); // lekki throttle, nie zajeżdżaj ich serwera
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Pobrano: {$ok} | Błędy: {$fail}");
        $this->line('Zdjęcia w: storage/app/public/' . self::SUBDIR . ' (jpg + webp), serwowane przez /storage.');

        return self::SUCCESS;
    }
}
