<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use Illuminate\Database\Seeder;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use Symfony\Component\Yaml\Yaml;

/**
 * Jednorazowy seeder REFRESHU treści — domknięcie pełnego re-audytu 2026-07-13.
 *
 * 4 blogi z flagami re-audytu: pozwolenie (539 zł = budynek, nie billboard),
 * Gdańsk (usunięte niepotwierdzone sygnatury wyroków), hub uchwała (kara /dzień
 * zamiast /miesiąc: 259,20 zł), samochód (Warszawa → Gdańsk, uchwała uchylona).
 *
 * Aktualizuje WYŁĄCZNIE treść (content/title/image_alt/image_prompt) po slug —
 * NIE dotyka status/published_at/created_at/id/slug. Uruchom raz na prod, potem SKASUJ:
 *   php artisan db:seed --class=RefreshReauditSeeder --force
 */
class RefreshReauditSeeder extends Seeder
{
    private const SLUGS = [
        'pozwolenie-na-tablice-reklamowa',
        'reklama-outdoor-gdansk',
        'uchwala-krajobrazowa-reklama',
        'reklama-na-samochodzie',
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
