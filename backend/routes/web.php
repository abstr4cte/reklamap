<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use App\Models\Advertisement;
use Illuminate\Support\Str;

Route::get('/', function () {
    return response()->json(['status' => 'ok'], 200);
});

// Sitemap.xml generator with cache
Route::get('/sitemap.xml', function () {
    // Cache sitemap for 1 hour (cleared when new ad is added/updated)
    $xml = Cache::remember('sitemap_xml', 3600, function () {
        $baseUrl = config('app.frontend_url');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        $now = now()->toAtomString();

        // Static pages
        $staticPages = [
            '/' => ['priority' => '1.0', 'changefreq' => 'daily'],
            '/powierzchnie-reklamowe' => ['priority' => '0.9', 'changefreq' => 'daily'],
            '/dodaj-powierzchnie-reklamowa' => ['priority' => '0.8', 'changefreq' => 'weekly'],
            '/blog' => ['priority' => '0.7', 'changefreq' => 'weekly'],
            '/faq' => ['priority' => '0.6', 'changefreq' => 'monthly'],
            '/kontakt' => ['priority' => '0.6', 'changefreq' => 'monthly'],
            '/regulamin' => ['priority' => '0.5', 'changefreq' => 'monthly'],
            '/polityka-prywatnosci' => ['priority' => '0.5', 'changefreq' => 'monthly'],
        ];

        foreach ($staticPages as $page => $config) {
            $xml .= '<url>';
            $xml .= '<loc>' . htmlspecialchars($baseUrl . $page) . '</loc>';
            $xml .= '<lastmod>' . $now . '</lastmod>';
            $xml .= '<changefreq>' . $config['changefreq'] . '</changefreq>';
            $xml .= '<priority>' . $config['priority'] . '</priority>';
            $xml .= '</url>';
        }

        // Blog category pages
        $blogCategories = ['poradniki', 'trendy', 'case-study', 'rynek-ooh', 'prawo-i-regulacje', 'lokalizacje'];
        foreach ($blogCategories as $cat) {
            $hasPublished = \App\Models\BlogPost::where('status', 'published')
                ->where('category', $cat)
                ->exists();
            if (!$hasPublished) {
                continue;
            }
            $xml .= '<url>';
            $xml .= '<loc>' . htmlspecialchars($baseUrl . '/blog/' . $cat) . '</loc>';
            $xml .= '<lastmod>' . $now . '</lastmod>';
            $xml .= '<changefreq>weekly</changefreq>';
            $xml .= '<priority>0.65</priority>';
            $xml .= '</url>';
        }

        // Category pages
        $categoryTypeMap = [
            'billboardy' => 'billboard', 'citylighty' => 'citylight', 'banery' => 'banner',
            'sciany-reklamowe' => 'wall', 'totemy-reklamowe' => 'totem', 'reklama-w-transporcie' => 'transport',
            'reklama-mobilna' => 'mobile', 'ekrany-led' => 'led_screen', 'inne' => 'other',
        ];
        foreach ($categoryTypeMap as $slug => $dbType) {
            $lastmod = \App\Models\Advertisement::where('type', $dbType)->where('is_active', true)->max('updated_at');
            $xml .= '<url>';
            $xml .= '<loc>' . htmlspecialchars($baseUrl . '/powierzchnie-reklamowe/' . $slug) . '</loc>';
            if ($lastmod) $xml .= '<lastmod>' . \Carbon\Carbon::parse($lastmod)->toAtomString() . '</lastmod>';
            $xml .= '<changefreq>daily</changefreq>';
            $xml .= '<priority>0.8</priority>';
            $xml .= '</url>';
        }

        // Popular cities pages
        $popularCities = [
            'Warszawa', 'Kraków', 'Wrocław', 'Poznań', 'Gdańsk', 'Łódź',
            'Katowice', 'Szczecin', 'Bydgoszcz', 'Lublin', 'Białystok', 'Gdynia',
        ];
        foreach ($popularCities as $city) {
            $citySlug = Str::slug($city);
            $lastmod = \App\Models\Advertisement::where('city', $city)->where('is_active', true)->max('updated_at');
            $xml .= '<url>';
            $xml .= '<loc>' . htmlspecialchars($baseUrl . '/powierzchnie-reklamowe/' . $citySlug) . '</loc>';
            if ($lastmod) $xml .= '<lastmod>' . \Carbon\Carbon::parse($lastmod)->toAtomString() . '</lastmod>';
            $xml .= '<changefreq>daily</changefreq>';
            $xml .= '<priority>0.7</priority>';
            $xml .= '</url>';
        }

        // City + Category combinations (top cities only for most important combinations)
        $topCities = ['Warszawa', 'Kraków', 'Wrocław', 'Poznań', 'Gdańsk', 'Łódź', 'Katowice'];
        $topCategories = ['billboardy' => 'billboard', 'citylighty' => 'citylight', 'banery' => 'banner', 'ekrany-led' => 'led_screen'];
        foreach ($topCities as $city) {
            $citySlug = Str::slug($city);
            foreach ($topCategories as $catSlug => $dbType) {
                $lastmod = \App\Models\Advertisement::where('type', $dbType)->where('city', $city)->where('is_active', true)->max('updated_at');
                $xml .= '<url>';
                $xml .= '<loc>' . htmlspecialchars($baseUrl . '/powierzchnie-reklamowe/' . $catSlug . '/' . $citySlug) . '</loc>';
                if ($lastmod) $xml .= '<lastmod>' . \Carbon\Carbon::parse($lastmod)->toAtomString() . '</lastmod>';
                $xml .= '<changefreq>daily</changefreq>';
                $xml .= '<priority>0.75</priority>';
                $xml .= '</url>';
            }
        }

        // All active advertisements
        $advertisements = \App\Models\Advertisement::where('is_active', true)
            ->orderBy('updated_at', 'desc')
            ->get();

        $typeMapping = [
            'billboard' => 'billboardy',
            'citylight' => 'citylighty',
            'led_screen' => 'ekrany-led',
            'banner' => 'banery',
            'wall' => 'sciany-reklamowe',
            'totem' => 'totemy-reklamowe',
            'transport' => 'reklama-w-transporcie',
            'mobile' => 'reklama-mobilna',
            'other' => 'inne'
        ];

        foreach ($advertisements as $ad) {
            $slug = Str::slug($ad->title);
            $typeSlug = $typeMapping[$ad->type] ?? 'inne';
            $citySlug = Str::slug($ad->city);

            $url = "/powierzchnia-reklamowa/{$typeSlug}/{$citySlug}/{$slug}-{$ad->id}";

            $xml .= '<url>';
            $xml .= '<loc>' . htmlspecialchars($baseUrl . $url) . '</loc>';
            $xml .= '<lastmod>' . $ad->updated_at->toAtomString() . '</lastmod>';
            $xml .= '<changefreq>weekly</changefreq>';
            $xml .= '<priority>0.7</priority>';
            $xml .= '</url>';
        }

        // Blog posts
        $blogPosts = \App\Models\BlogPost::where('status', 'published')
            ->orderBy('published_at', 'desc')
            ->get(['slug', 'category', 'published_at', 'updated_at']);

        foreach ($blogPosts as $post) {
            $xml .= '<url>';
            $xml .= '<loc>' . htmlspecialchars($baseUrl . '/blog/' . $post->category . '/' . $post->slug) . '</loc>';
            $xml .= '<lastmod>' . ($post->updated_at ?? $post->published_at)->toAtomString() . '</lastmod>';
            $xml .= '<changefreq>monthly</changefreq>';
            $xml .= '<priority>0.6</priority>';
            $xml .= '</url>';
        }

        $xml .= '</urlset>';

        return $xml;
    });

    return response($xml, 200)
        ->header('Content-Type', 'application/xml');
})->name('sitemap');
