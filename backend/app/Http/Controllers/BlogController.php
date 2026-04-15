<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    /**
     * Get all published blog posts, optionally filtered by category
     */
    public function index(Request $request)
    {
        $query = BlogPost::where('status', 'published')
            ->with('user')
            ->orderBy('published_at', 'desc');

        // Filter by category if provided
        if ($request->has('category') && $request->category !== 'wszystkie') {
            $query->where('category', $request->category);
        }

        $posts = $query->get()->map(function ($post) {
            // Return image URL only if file exists
            $imageUrl = null;
            if ($post->image) {
                if (filter_var($post->image, FILTER_VALIDATE_URL)) {
                    $imageUrl = $post->image;
                } else {
                    $imagePath = storage_path('app/public/' . $post->image);
                    if (file_exists($imagePath)) {
                        $imageUrl = url('storage/' . $post->image);
                    }
                }
            }
            
            return [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'excerpt' => $post->excerpt,
                'content' => $post->content,
                'category' => $post->category,
                'image' => $imageUrl,
                'imageAlt' => $post->image_alt ?? $post->title,
                'date' => $post->published_at ? $post->published_at->locale('pl')->translatedFormat('d F Y') : $post->created_at->locale('pl')->translatedFormat('d F Y'),
                'dateIso' => $post->published_at ? $post->published_at->toIso8601String() : $post->created_at->toIso8601String(),
                'readTime' => $this->estimateReadTime($post->content),
                'author' => $post->user->name ?? 'Anonimowy',
            ];
        });

        return response()->json($posts, 200, [], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    }

    /**
     * Get a single blog post by slug
     */
    public function show($slug)
    {
        $post = BlogPost::where('slug', $slug)
            ->where('status', 'published')
            ->with('user')
            ->firstOrFail();

        // Return image URL only if file exists
        $imageUrl = null;
        if ($post->image) {
            if (filter_var($post->image, FILTER_VALIDATE_URL)) {
                $imageUrl = $post->image;
            } else {
                $imagePath = storage_path('app/public/' . $post->image);
                if (file_exists($imagePath)) {
                    $imageUrl = url('storage/' . $post->image);
                }
            }
        }

        return response()->json([
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'excerpt' => $post->excerpt,
            'content' => $post->content,
            'category' => $post->category,
            'image' => $imageUrl,
            'imageAlt' => $post->image_alt ?? $post->title,
            'date' => $post->published_at ? $post->published_at->locale('pl')->translatedFormat('d F Y') : $post->created_at->locale('pl')->translatedFormat('d F Y'),
            'dateIso' => $post->published_at ? $post->published_at->toIso8601String() : $post->created_at->toIso8601String(),
            'readTime' => $this->estimateReadTime($post->content),
            'author' => $post->user->name ?? 'Anonimowy',
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    }

    /**
     * Estimate read time based on content length
     */
    private function estimateReadTime($content): string
    {
        // Remove HTML tags
        $text = strip_tags($content);
        
        // Count words (average reading speed is 200 words per minute)
        $wordCount = str_word_count($text);
        $minutes = ceil($wordCount / 200);
        
        return $minutes . ' min';
    }
}
