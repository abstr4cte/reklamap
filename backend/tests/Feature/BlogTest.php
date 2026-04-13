<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Blog Tests
 *
 * Tests for blog post listing and individual post retrieval.
 */
class BlogTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test fetching all published blog posts
     */
    public function test_can_fetch_all_published_blog_posts(): void
    {
        $user = User::factory()->create();

        BlogPost::factory()->count(3)->create([
            'status' => 'published',
            'user_id' => $user->id,
        ]);

        $response = $this->getJson('/api/blog', $this->appKeyHeaders());

        $response->assertStatus(200);
        $response->assertJsonCount(3);
        $response->assertJsonStructure([
            '*' => [
                'id',
                'title',
                'slug',
                'excerpt',
                'content',
                'category',
                'image',
                'date',
                'readTime',
                'author',
            ]
        ]);
    }

    /**
     * Test draft posts are not returned
     */
    public function test_draft_posts_are_not_returned(): void
    {
        $user = User::factory()->create();

        BlogPost::factory()->count(2)->create([
            'status' => 'published',
            'user_id' => $user->id,
        ]);

        BlogPost::factory()->count(3)->create([
            'status' => 'draft',
            'user_id' => $user->id,
        ]);

        $response = $this->getJson('/api/blog', $this->appKeyHeaders());

        $response->assertStatus(200);
        $response->assertJsonCount(2); // Only published posts
    }

    /**
     * Test fetching blog post by slug
     */
    public function test_can_fetch_blog_post_by_slug(): void
    {
        $user = User::factory()->create(['name' => 'John Doe']);

        $post = BlogPost::factory()->create([
            'title' => 'Test Blog Post',
            'slug' => 'test-blog-post',
            'status' => 'published',
            'user_id' => $user->id,
            'content' => 'This is a test blog post content.',
        ]);

        $response = $this->getJson('/api/blog/test-blog-post', $this->appKeyHeaders());

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $post->id,
            'title' => 'Test Blog Post',
            'slug' => 'test-blog-post',
            'author' => 'John Doe',
        ]);
    }

    /**
     * Test fetching non-existent blog post returns 404
     */
    public function test_fetching_non_existent_post_returns_404(): void
    {
        $response = $this->getJson('/api/blog/non-existent-slug', $this->appKeyHeaders());

        $response->assertStatus(404);
    }

    /**
     * Test fetching draft post by slug returns 404
     */
    public function test_fetching_draft_post_returns_404(): void
    {
        $user = User::factory()->create();

        BlogPost::factory()->create([
            'slug' => 'draft-post',
            'status' => 'draft',
            'user_id' => $user->id,
        ]);

        $response = $this->getJson('/api/blog/draft-post', $this->appKeyHeaders());

        $response->assertStatus(404);
    }

    /**
     * Test filtering blog posts by category
     */
    public function test_can_filter_blog_posts_by_category(): void
    {
        $user = User::factory()->create();

        BlogPost::factory()->count(2)->create([
            'status' => 'published',
            'category' => 'poradniki',
            'user_id' => $user->id,
        ]);

        BlogPost::factory()->count(3)->create([
            'status' => 'published',
            'category' => 'nowosci',
            'user_id' => $user->id,
        ]);

        $response = $this->getJson('/api/blog?category=poradniki', $this->appKeyHeaders());

        $response->assertStatus(200);
        $response->assertJsonCount(2);
        
        // Verify all returned posts have correct category
        $data = $response->json();
        foreach ($data as $post) {
            $this->assertEquals('poradniki', $post['category']);
        }
    }

    /**
     * Test blog posts are ordered by published_at descending
     */
    public function test_blog_posts_are_ordered_by_published_at(): void
    {
        $user = User::factory()->create();

        $old = BlogPost::factory()->create([
            'status' => 'published',
            'published_at' => now()->subDays(3),
            'user_id' => $user->id,
        ]);

        $middle = BlogPost::factory()->create([
            'status' => 'published',
            'published_at' => now()->subDays(1),
            'user_id' => $user->id,
        ]);

        $new = BlogPost::factory()->create([
            'status' => 'published',
            'published_at' => now(),
            'user_id' => $user->id,
        ]);

        $response = $this->getJson('/api/blog', $this->appKeyHeaders());

        $response->assertStatus(200);
        $data = $response->json();

        // Newest should be first
        $this->assertEquals($new->id, $data[0]['id']);
        $this->assertEquals($middle->id, $data[1]['id']);
        $this->assertEquals($old->id, $data[2]['id']);
    }

    /**
     * Test read time estimation
     */
    public function test_read_time_is_estimated(): void
    {
        $user = User::factory()->create();

        // Create post with ~600 words (should be 3 min at 200 words/min)
        $content = str_repeat('word ', 600);

        $post = BlogPost::factory()->create([
            'status' => 'published',
            'content' => $content,
            'user_id' => $user->id,
        ]);

        $response = $this->getJson("/api/blog/{$post->slug}", $this->appKeyHeaders());

        $response->assertStatus(200);
        $this->assertStringContainsString('min', $response->json('readTime'));
    }

    /**
     * Test blog listing requires app key
     */
    public function test_blog_listing_requires_app_key(): void
    {
        // Without app key
        $response = $this->getJson('/api/blog');
        $response->assertStatus(403);

        // With invalid app key
        $response = $this->getJson('/api/blog', ['X-App-Key' => 'invalid-key']);
        $response->assertStatus(403);
    }

    /**
     * Test blog post detail requires app key
     */
    public function test_blog_post_detail_requires_app_key(): void
    {
        $user = User::factory()->create();
        $post = BlogPost::factory()->create([
            'status' => 'published',
            'user_id' => $user->id,
        ]);

        // Without app key
        $response = $this->getJson("/api/blog/{$post->slug}");
        $response->assertStatus(403);

        // With invalid app key
        $response = $this->getJson("/api/blog/{$post->slug}", ['X-App-Key' => 'invalid-key']);
        $response->assertStatus(403);
    }
}
