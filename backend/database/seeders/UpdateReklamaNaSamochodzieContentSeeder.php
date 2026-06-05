<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use Illuminate\Database\Seeder;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use Symfony\Component\Yaml\Yaml;

/**
 * Seeder JEDNORAZOWY — aktualizuje WYŁĄCZNIE treść (content) posta
 * `reklama-na-samochodzie` po rozbudowie o sekcję reklamy mobilnej/przyczepek.
 *
 * Celowo NIE dotyka: published_at, status, user_id, slug — żeby już
 * opublikowany artykuł nie wrócił do draftu i nie zmienił daty publikacji.
 * Nie tworzy posta, jeśli nie istnieje (żeby nie wstawić przypadkiem draftu).
 *
 * Uruchomienie: php artisan db:seed --class=UpdateReklamaNaSamochodzieContentSeeder
 */
class UpdateReklamaNaSamochodzieContentSeeder extends Seeder
{
    private const SLUG = 'reklama-na-samochodzie';

    public function run(): void
    {
        $matches = glob(base_path('../reklamap-os/blog/posts/*_' . self::SLUG . '.md'));

        if (empty($matches)) {
            $this->command->error('Nie znaleziono pliku .md dla slug: ' . self::SLUG);
            return;
        }

        $post = BlogPost::where('slug', self::SLUG)->first();

        if (!$post) {
            $this->command->warn('Post nie istnieje w bazie — nic nie aktualizuję (świadomie nie tworzę nowego, żeby nie wstawić draftu).');
            return;
        }

        $raw  = file_get_contents($matches[0]);
        $body = $this->extractBody($raw);
        $body = preg_replace('/<!--.*?-->/s', '', $body);

        $html = (new GithubFlavoredMarkdownConverter([
            'html_input'         => 'allow',
            'allow_unsafe_links' => true,
        ]))->convert($body)->getContent();

        $oldLen = mb_strlen((string) $post->content);

        // Aktualizujemy TYLKO content. published_at i status zostają nietknięte.
        $post->content = $html;
        $post->save();

        $this->command->info(sprintf(
            'Zaktualizowano treść "%s": %d → %d znaków HTML. status=%s (bez zmian), published_at=%s (bez zmian).',
            self::SLUG,
            $oldLen,
            mb_strlen($html),
            $post->status,
            (string) $post->published_at
        ));
    }

    private function extractBody(string $raw): string
    {
        $stripped = preg_replace('/^\s*<!--.*?-->\s*/s', '', $raw, 1);

        if (!str_starts_with(ltrim($stripped), '---')) {
            return $raw;
        }

        $parts = preg_split('/^---\s*$/m', $stripped, 3);

        if (count($parts) < 3) {
            return $raw;
        }

        Yaml::parse(trim($parts[1])); // walidacja front matter

        return ltrim($parts[2]);
    }
}
