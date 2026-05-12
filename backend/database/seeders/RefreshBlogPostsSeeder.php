<?php

namespace Database\Seeders;

/**
 * ⚠️ SEEDER JEDNORAZOWY — do usunięcia po użyciu.
 *
 * Aktualizuje treść/meta KONKRETNYCH postów blogowych z plików .md, bez zmiany `status`
 * i bez wysyłania newslettera (idzie przez model `BlogPost::update()`, nie przez panel Filament,
 * więc hook `afterSave` z mailem się nie odpala). W przeciwieństwie do `BlogPostsSeeder` —
 * nie dotyka żadnych innych postów ani statusów.
 *
 * Użycie (na produkcji, po deployu):
 *   php artisan db:seed --class=RefreshBlogPostsSeeder
 *
 * Po wykonaniu: USUŃ ten plik.
 */

use App\Models\BlogPost;
use Illuminate\Database\Seeder;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use Symfony\Component\Yaml\Yaml;

class RefreshBlogPostsSeeder extends Seeder
{
    /** Slugi postów do odświeżenia (i tylko ich). */
    private const SLUGS = [
        'billboard-reklama',
        'citylight-reklama',
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

        $files = glob("{$postsDir}/*.md");
        sort($files);

        $done = 0;

        foreach ($files as $file) {
            $raw = file_get_contents($file);
            [$frontMatter, $body] = $this->parseFrontMatter($raw);

            $slug = $frontMatter['slug'] ?? null;
            if (!$slug || !in_array($slug, self::SLUGS, true)) {
                continue;
            }

            $post = BlogPost::where('slug', $slug)->first();
            if (!$post) {
                $this->command->warn("Pominięto — brak posta w bazie: {$slug}");
                continue;
            }

            $body = preg_replace('/<!--.*?-->/s', '', $body);
            $html = $converter->convert($body)->getContent();

            // Aktualizujemy tylko treść/meta z pliku — `status` zostaje nietknięty.
            $post->update([
                'title'        => $frontMatter['title']        ?? $post->title,
                'category'     => $frontMatter['category']     ?? $post->category,
                'image_alt'    => $frontMatter['image_alt']    ?? $post->image_alt,
                'image_prompt' => $frontMatter['image_prompt'] ?? $post->image_prompt,
                'published_at' => $frontMatter['published_at'] ?? $post->published_at,
                'content'      => $html,
            ]);

            $this->command->info("Zaktualizowano (status: {$post->status}): {$slug}");
            $done++;
        }

        $this->command->info("Gotowe — odświeżono {$done} z " . count(self::SLUGS) . " postów. Pamiętaj usunąć ten seeder.");
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

        $frontMatter = Yaml::parse(trim($parts[1]));
        $body        = ltrim($parts[2]);

        return [$frontMatter, $body];
    }
}
