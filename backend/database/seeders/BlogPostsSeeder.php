<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Database\Seeder;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use Symfony\Component\Yaml\Yaml;

class BlogPostsSeeder extends Seeder
{
    /**
     * Czyta pliki .md z reklamap-os/blog/posts/, parsuje front matter i konwertuje
     * markdown do HTML. Każdy post jest upsertowany po slug — bezpieczne wielokrotne uruchamianie.
     */
    public function run(): void
    {
        $author = User::where('is_admin', true)->first();

        if (!$author) {
            $this->command->warn('Brak użytkownika admina — uruchom najpierw AdminUserSeeder.');
            return;
        }

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

        foreach ($files as $file) {
            $raw = file_get_contents($file);

            [$frontMatter, $body] = $this->parseFrontMatter($raw);

            if (empty($frontMatter['slug'])) {
                $this->command->warn("Brak slug w pliku: " . basename($file));
                continue;
            }

            $body = preg_replace('/<!--.*?-->/s', '', $body);
            $html = $converter->convert($body)->getContent();

            if (BlogPost::where('slug', $frontMatter['slug'])->exists()) {
                $this->command->line("Pominięto (już istnieje): {$frontMatter['slug']}");
                continue;
            }

            BlogPost::create([
                'slug'         => $frontMatter['slug'],
                'title'        => $frontMatter['title']        ?? '',
                'category'     => $frontMatter['category']     ?? 'poradniki',
                'image_alt'    => $frontMatter['image_alt']    ?? '',
                'image_prompt' => $frontMatter['image_prompt'] ?? '',
                'published_at' => $frontMatter['published_at'] ?? now(),
                'content'      => $html,
                'user_id'      => $author->id,
                'status'       => 'draft',
            ]);

            $this->command->info("Dodano nowy post: {$frontMatter['slug']}");
        }

        $this->command->info('Gotowe — ' . count($files) . ' postów zsynchronizowanych z plików .md.');
    }

    private function parseFrontMatter(string $raw): array
    {
        if (!str_starts_with(ltrim($raw), '---')) {
            return [[], $raw];
        }

        $parts = preg_split('/^---\s*$/m', $raw, 3);

        if (count($parts) < 3) {
            return [[], $raw];
        }

        $frontMatter = Yaml::parse(trim($parts[1]));
        $body        = ltrim($parts[2]);

        return [$frontMatter, $body];
    }
}
