<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use Illuminate\Database\Seeder;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use Symfony\Component\Yaml\Yaml;

/**
 * Jednorazowy seeder REFRESHU treści — korekty audytu uchwał krajobrazowych 2026-07-12/13.
 *
 * Aktualizuje WYŁĄCZNIE pola treściowe (content, title, image_alt, image_prompt) istniejących
 * postów po `slug` — NIE dotyka status, published_at, created_at, id, slug, category. Dzięki temu:
 * nie przedatowuje („Dodano dziś"), nie cofa publikacji, nie zmienia URL (slug-{id}), nie osierica
 * statystyk. Zgodne z zasadą projektu: update W MIEJSCU, nigdy delete+create.
 *
 * Uruchom raz na prod (po deployu backendu z poprawionymi plikami .md), potem SKASUJ ten plik:
 *   php artisan db:seed --class=RefreshUchwalyKorektorSeeder
 *
 * Wzorzec analogiczny do RefreshDoohCennikSeeder (jednorazowy, kasowany po użyciu).
 */
class RefreshUchwalyKorektorSeeder extends Seeder
{
    /** Slugi poprawione w audycie uchwał 2026-07-12/13 (4 miasta + hub prawny — celowany refresh). */
    private const SLUGS = [
        'reklama-outdoor-poznan',
        'reklama-outdoor-gdansk',
        'reklama-outdoor-lodz',
        'reklama-outdoor-krakow',
        'uchwala-krajobrazowa-reklama',
    ];

    public function run(): void
    {
        $postsDir = base_path('../reklamap-os/blog/posts');

        if (!is_dir($postsDir)) {
            $this->command->error("Katalog z postami nie istnieje: {$postsDir}");
            return;
        }

        $converter = new GithubFlavoredMarkdownConverter([
            'html_input'         => 'allow',
            'allow_unsafe_links' => true,
        ]);

        $updated = 0;

        foreach (self::SLUGS as $slug) {
            $matches = glob("{$postsDir}/*_{$slug}.md");
            if (empty($matches)) {
                $this->command->warn("Brak pliku .md dla sluga: {$slug}");
                continue;
            }

            [$frontMatter, $body] = $this->parseFrontMatter(file_get_contents($matches[0]));

            $post = BlogPost::where('slug', $slug)->first();
            if (!$post) {
                $this->command->warn("Post nie istnieje w bazie (pominięto): {$slug}");
                continue;
            }

            $body = preg_replace('/<!--.*?-->/s', '', $body);
            $html = $converter->convert($body)->getContent();

            // Aktualizacja WYŁĄCZNIE pól treściowych — bez status / published_at / created_at / slug.
            $post->update([
                'title'        => $frontMatter['title']        ?? $post->title,
                'image_alt'    => $frontMatter['image_alt']    ?? $post->image_alt,
                'image_prompt' => $frontMatter['image_prompt'] ?? $post->image_prompt,
                'content'      => $html,
            ]);

            $updated++;
            $this->command->info("Zaktualizowano treść: {$slug} (status={$post->status}, published_at={$post->published_at} — nietknięte)");
        }

        $this->command->info("Gotowe — zaktualizowano treść {$updated}/" . count(self::SLUGS) . " postów. Skasuj ten seeder po użyciu.");
    }

    private function parseFrontMatter(string $raw): array
    {
        $stripped = preg_replace('/^\s*<!--.*?-->\s*/s', '', $raw, 1);

        if (!str_starts_with(ltrim($stripped), '---')) {
            return [[], $raw];
        }

        $parts = preg_split('/^---\s*$/m', $stripped, 3);

        if (count($parts) < 3) {
            return [[], $raw];
        }

        return [Yaml::parse(trim($parts[1])), ltrim($parts[2])];
    }
}
