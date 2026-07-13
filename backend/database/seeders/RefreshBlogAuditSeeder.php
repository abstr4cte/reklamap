<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use Illuminate\Database\Seeder;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use Symfony\Component\Yaml\Yaml;

/**
 * Jednorazowy seeder REFRESHU treści — audyt faktograficzny 23 blogów 2026-07-13.
 *
 * Aktualizuje WYŁĄCZNIE pola treściowe (content, title, image_alt, image_prompt) istniejących
 * postów po `slug` — NIE dotyka status, published_at, created_at, id, slug, category. Zgodne
 * z zasadą projektu: update W MIEJSCU, nigdy delete+create.
 *
 * Zakres: korekty faktów prawnych/fiskalnych/statystycznych wykryte audytem (opłata reklamowa 2026
 * 3,89/0,36, kary art. 37d, podstawa art. 37a, status uchwał miast, Prawo budowlane Dz.U. 2025 poz. 418,
 * odległości od dróg art. 43, podatki, rynek OOH). Weryfikacja: ground-truth + Perplexity Q1-Q6.
 *
 * Uruchom raz na prod (po deployu backendu z poprawionymi plikami .md), potem SKASUJ ten plik:
 *   php artisan db:seed --class=RefreshBlogAuditSeeder --force
 */
class RefreshBlogAuditSeeder extends Seeder
{
    /** Slugi poprawione w audycie faktów 2026-07-13 (23 blogi — celowany refresh treści). */
    private const SLUGS = [
        'jak-wybrac-powierzchnie-reklamowa',
        'reklama-na-samochodzie',
        'reklama-outdoor-warszawa',
        'reklama-outdoor-wroclaw',
        'citylight-reklama',
        'reklama-zewnetrzna',
        'murale-reklamowe',
        'tablica-reklamowa',
        'telebim-ekran-led-reklama',
        'totem-reklamowy',
        'baner-reklamowy-cena',
        'reklama-outdoor-poznan',
        'billboard-reklama',
        'oplata-reklamowa',
        'reklama-outdoor-katowice',
        'reklama-outdoor-olsztyn',
        'reklama-outdoor-bydgoszcz',
        'dooh-reklama-programatyczna',
        'ekran-led-cena',
        'reklama-outdoor-lublin',
        'jak-zarobic-na-wynajmie-powierzchni-reklamowej',
        'reklama-bez-pozwolenia-kary',
        'reklama-na-elewacji-wspolnoty',
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
