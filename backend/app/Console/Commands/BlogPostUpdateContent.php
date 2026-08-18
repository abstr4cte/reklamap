<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use Illuminate\Console\Command;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use Symfony\Component\Yaml\Yaml;

class BlogPostUpdateContent extends Command
{
    /**
     * @var string
     */
    protected $signature = 'blog:update-content {slug : Slug posta, który już istnieje w bazie}';

    /**
     * @var string
     */
    protected $description = 'Aktualizuje treść JUŻ ISTNIEJĄCEGO posta blogowego z pliku .md — update w miejscu, bez ruszania status/published_at/id. Do korekt merytorycznych w opublikowanych artykułach (BlogPostsSeeder pomija istniejące slugi).';

    public function handle(): int
    {
        $slug = (string) $this->argument('slug');

        $post = BlogPost::where('slug', $slug)->first();

        if (! $post) {
            $this->error("Nie znaleziono posta o slugu '{$slug}' w bazie — to nie jest komenda do tworzenia nowych postów, użyj BlogPostsSeeder.");
            return self::FAILURE;
        }

        $postsDir = base_path('../reklamap-os/blog/posts');
        $matches = glob("{$postsDir}/*_{$slug}.md");

        if (empty($matches)) {
            $this->error("Nie znaleziono pliku .md dla sluga '{$slug}' w {$postsDir}.");
            return self::FAILURE;
        }

        $raw = file_get_contents($matches[0]);
        [$frontMatter, $body] = $this->parseFrontMatter($raw);

        if (empty($frontMatter['slug']) || $frontMatter['slug'] !== $slug) {
            $this->error("Slug w pliku .md nie zgadza się z argumentem komendy.");
            return self::FAILURE;
        }

        $body = preg_replace('/<!--.*?-->/s', '', $body);
        $converter = new GithubFlavoredMarkdownConverter([
            'html_input'         => 'allow',
            'allow_unsafe_links' => true,
        ]);
        $html = $converter->convert($body)->getContent();

        $post->update([
            'title'        => $frontMatter['title']        ?? $post->title,
            'category'     => $frontMatter['category']     ?? $post->category,
            'image_alt'    => $frontMatter['image_alt']    ?? $post->image_alt,
            'image_prompt' => $frontMatter['image_prompt'] ?? $post->image_prompt,
            'content'      => $html,
        ]);

        $this->info("Zaktualizowano treść posta '{$slug}' (status i data publikacji bez zmian: status={$post->status}).");

        return self::SUCCESS;
    }

    private function parseFrontMatter(string $raw): array
    {
        $stripped = preg_replace('/^\s*<!--.*?-->\s*/s', '', $raw, 1);

        if (! str_starts_with(ltrim($stripped), '---')) {
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
