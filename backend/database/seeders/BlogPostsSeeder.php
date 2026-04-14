<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Database\Seeder;

class BlogPostsSeeder extends Seeder
{
    /**
     * Każdy post jest wstawiany tylko raz — sprawdzamy slug przed insertem.
     * Aby dodać nowy post: dorzuć wpis do tablicy $posts poniżej.
     */
    public function run(): void
    {
        $author = User::where('is_admin', true)->first();

        if (!$author) {
            $this->command->warn('Brak użytkownika admina — uruchom najpierw AdminUserSeeder.');
            return;
        }

        $posts = [
            // Tutaj będą dodawane kolejne posty przez agenta.
            // Format:
            // [
            //     'title'            => '',
            //     'slug'             => '',
            //     'category'         => '',
            //     'content'          => '',
            //     'image_prompt'     => '',
            //     'status'           => 'draft',
            //     'published_at'     => null,
            //     'created_at'       => '',
            // ],
        ];

        foreach ($posts as $post) {
            if (BlogPost::where('slug', $post['slug'])->exists()) {
                $this->command->line("Pomijam (już istnieje): {$post['slug']}");
                continue;
            }

            BlogPost::create(array_merge($post, [
                'user_id' => $author->id,
                'status'  => 'draft',
            ]));

            $this->command->info("Dodano: {$post['slug']}");
        }
    }
}
