<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use Illuminate\Database\Seeder;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use Symfony\Component\Yaml\Yaml;

/**
 * ⚠️ SEEDER TYMCZASOWY — DO SKASOWANIA PO URUCHOMIENIU (nie commitować).
 *
 * Force-update treści OPUBLIKOWANYCH artykułów z plików .md, BEZ zmiany statusu
 * i published_at. Główny BlogPostsSeeder POMIJA istniejące slugi (nie aktualizuje
 * treści opublikowanych), dlatego do wepchnięcia poprawek do żywych postów potrzebny
 * jest ten jednorazowy seeder.
 *
 * Zakres tej sesji: poprawka cennika AMS (data 1.07.2024 + realne CPM) w `dooh`
 * oraz przycięcie sekcji cenowej (anti-kanibalizacja z `ekran-led-cena`) w `telebim`.
 *
 * Użycie:
 *   php artisan db:seed --class=RefreshPublishedPostsSeeder
 *   rm database/seeders/RefreshPublishedPostsSeeder.php   # skasuj po wykonaniu
 *
 * UWAGA: `telebim` linkuje do `ekran-led-cena` — najpierw opublikuj `ekran-led-cena`,
 * inaczej link w telebimie prowadzi do nieopublikowanego artykułu (404).
 */
class RefreshPublishedPostsSeeder extends Seeder
{
    /** slug => relatywna ścieżka pliku .md (względem repo) */
    private const POSTS = [
        'dooh-reklama-programatyczna' => 'reklamap-os/blog/posts/20260525232247_dooh-reklama-programatyczna.md',
        'telebim-ekran-led-reklama'   => 'reklamap-os/blog/posts/20260414061100_telebim-ekran-led-reklama.md',
    ];

    public function run(): void
    {
        $converter = new GithubFlavoredMarkdownConverter([
            'html_input'         => 'allow',
            'allow_unsafe_links' => true,
        ]);

        foreach (self::POSTS as $slug => $rel) {
            $file = base_path('../' . $rel);

            if (!is_file($file)) {
                $this->command->error("Brak pliku: {$file}");
                continue;
            }

            $post = BlogPost::where('slug', $slug)->first();

            if (!$post) {
                $this->command->warn("Pomijam (brak w bazie): {$slug} — najpierw zwykły seed.");
                continue;
            }

            $stripped = preg_replace('/^\s*<!--.*?-->\s*/s', '', file_get_contents($file), 1);
            $parts    = preg_split('/^---\s*$/m', $stripped, 3);

            if (count($parts) < 3) {
                $this->command->error("Zły front matter: {$slug}");
                continue;
            }

            $frontMatter = Yaml::parse(trim($parts[1]));
            $body        = preg_replace('/<!--.*?-->/s', '', ltrim($parts[2]));
            $html        = $converter->convert($body)->getContent();

            $statusBefore = $post->status;

            // Aktualizujemy WYŁĄCZNIE treść/meta z pliku. status i published_at NIE są dotykane.
            $post->update([
                'title'     => $frontMatter['title']     ?? $post->title,
                'image_alt' => $frontMatter['image_alt'] ?? $post->image_alt,
                'content'   => $html,
            ]);

            $this->command->info("Zaktualizowano: {$slug} (status bez zmian: {$statusBefore}).");
        }

        $this->command->warn('PAMIĘTAJ: skasuj ten seeder — rm database/seeders/RefreshPublishedPostsSeeder.php');
    }
}
