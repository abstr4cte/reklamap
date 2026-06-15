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

        // Realny lastmod z bazy — nie używamy now(), bo Google traci zaufanie do sitemapy
        // gdy lastmod zmienia się przy każdym pobraniu mimo że treść się nie zmieniła.
        $latestAdAt = \App\Models\Advertisement::where('is_active', true)->max('updated_at');
        $latestBlogAt = \App\Models\BlogPost::where('status', 'published')->max('updated_at');

        $latestAdIso = $latestAdAt ? \Carbon\Carbon::parse($latestAdAt)->toAtomString() : null;
        $latestBlogIso = $latestBlogAt ? \Carbon\Carbon::parse($latestBlogAt)->toAtomString() : null;

        // Strony "regulaminowe" — stała data ostatniej zmiany contentu (aktualizować ręcznie przy edycji).
        $legalLastmod = \Carbon\Carbon::parse('2026-04-01T00:00:00+02:00')->toAtomString();

        $homeLastmod = collect([$latestAdIso, $latestBlogIso])->filter()->max();

        // Static pages
        $staticPages = [
            '/' => ['priority' => '1.0', 'changefreq' => 'daily', 'lastmod' => $homeLastmod],
            '/powierzchnie-reklamowe' => ['priority' => '0.9', 'changefreq' => 'daily', 'lastmod' => $latestAdIso],
            '/dodaj-powierzchnie-reklamowa' => ['priority' => '0.8', 'changefreq' => 'weekly', 'lastmod' => $legalLastmod],
            '/blog' => ['priority' => '0.7', 'changefreq' => 'weekly', 'lastmod' => $latestBlogIso],
            '/faq' => ['priority' => '0.6', 'changefreq' => 'monthly', 'lastmod' => $legalLastmod],
            '/kontakt' => ['priority' => '0.6', 'changefreq' => 'monthly', 'lastmod' => $legalLastmod],
            '/regulamin' => ['priority' => '0.5', 'changefreq' => 'monthly', 'lastmod' => $legalLastmod],
            '/polityka-prywatnosci' => ['priority' => '0.5', 'changefreq' => 'monthly', 'lastmod' => $legalLastmod],
        ];

        foreach ($staticPages as $page => $config) {
            $xml .= '<url>';
            $xml .= '<loc>' . htmlspecialchars($baseUrl . $page) . '</loc>';
            if (!empty($config['lastmod'])) {
                $xml .= '<lastmod>' . $config['lastmod'] . '</lastmod>';
            }
            $xml .= '<changefreq>' . $config['changefreq'] . '</changefreq>';
            $xml .= '<priority>' . $config['priority'] . '</priority>';
            $xml .= '</url>';
        }

        // Blog category pages
        $blogCategories = ['poradniki', 'trendy', 'case-study', 'rynek-ooh', 'prawo-i-regulacje', 'lokalizacje'];
        foreach ($blogCategories as $cat) {
            $catLastmod = \App\Models\BlogPost::where('status', 'published')
                ->where('category', $cat)
                ->max('updated_at');
            if (!$catLastmod) {
                continue;
            }
            $xml .= '<url>';
            $xml .= '<loc>' . htmlspecialchars($baseUrl . '/blog/' . $cat) . '</loc>';
            $xml .= '<lastmod>' . \Carbon\Carbon::parse($catLastmod)->toAtomString() . '</lastmod>';
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

        // Próg cienkiej strony — MUSI być zgodny z THIN_PAGE_THRESHOLD w
        // frontend/src/views/ListingsPage.vue. Strony miasta / typ×miasto z mniejszą
        // liczbą aktywnych ogłoszeń front oznacza `noindex`, więc nie wolno ich
        // reklamować w sitemapie (sprzeczny sygnał + marnowanie crawl budgetu na cienkie
        // near-duplikaty — potwierdzone w GSC 2026-05-29: 149 URL-i „Wykryta – obecnie
        // niezindeksowana"). Leaf-ogłoszenia zostają bez progu.
        $thinPageThreshold = 3;

        // Strony miast — miasta z co najmniej $thinPageThreshold aktywnymi ogłoszeniami
        // (wcześniej wszystkie, co zalewało indeks cienkimi stronami wiosek z 1 ofertą).
        $citiesAggregated = \App\Models\Advertisement::where('is_active', true)
            ->select('city', \Illuminate\Support\Facades\DB::raw('MAX(updated_at) as lastmod'))
            ->groupBy('city')
            ->havingRaw('COUNT(*) >= ?', [$thinPageThreshold])
            ->get();

        foreach ($citiesAggregated as $row) {
            $citySlug = Str::slug($row->city);
            $xml .= '<url>';
            $xml .= '<loc>' . htmlspecialchars($baseUrl . '/powierzchnie-reklamowe/' . $citySlug) . '</loc>';
            $xml .= '<lastmod>' . \Carbon\Carbon::parse($row->lastmod)->toAtomString() . '</lastmod>';
            $xml .= '<changefreq>daily</changefreq>';
            $xml .= '<priority>0.7</priority>';
            $xml .= '</url>';
        }

        // Kombinacje typ × miasto — pary z co najmniej $thinPageThreshold ogłoszeniami
        // (próg jw. — cienkie pary typ×miasto są noindex we froncie, więc poza sitemapą).
        // Single query z GROUP BY zamiast pętli (była ~50 miast × 9 typów = 450 query).
        $typeDbToSlug = [
            'billboard' => 'billboardy', 'citylight' => 'citylighty', 'banner' => 'banery',
            'wall' => 'sciany-reklamowe', 'totem' => 'totemy-reklamowe', 'transport' => 'reklama-w-transporcie',
            'mobile' => 'reklama-mobilna', 'led_screen' => 'ekrany-led', 'other' => 'inne',
        ];

        $typeCityCombos = \App\Models\Advertisement::where('is_active', true)
            ->select('type', 'city', \Illuminate\Support\Facades\DB::raw('MAX(updated_at) as lastmod'))
            ->groupBy('type', 'city')
            ->havingRaw('COUNT(*) >= ?', [$thinPageThreshold])
            ->get();

        foreach ($typeCityCombos as $row) {
            $catSlug = $typeDbToSlug[$row->type] ?? 'inne';
            $citySlug = Str::slug($row->city);
            $xml .= '<url>';
            $xml .= '<loc>' . htmlspecialchars($baseUrl . '/powierzchnie-reklamowe/' . $catSlug . '/' . $citySlug) . '</loc>';
            $xml .= '<lastmod>' . \Carbon\Carbon::parse($row->lastmod)->toAtomString() . '</lastmod>';
            $xml .= '<changefreq>daily</changefreq>';
            $xml .= '<priority>0.75</priority>';
            $xml .= '</url>';
        }

        // Leaf-ogłoszenia: tylko REALNIE WYNAJMOWALNE. Nośniki `reserved` (zajęte) i
        // `draft` (niepubliczne) nie trafiają do sitemapy — strona takiego nośnika
        // sygnalizuje OutOfStock (AdDetailPage), więc reklamowanie jej w Google to
        // crawl waste + thin/near-duplicate (potwierdzone w GSC 2026-06-15 po imporcie
        // Optokom: 192 nośniki, część reserved, skok „Wykryta–niezindeksowana" 80→289).
        // Strony-agregaty miast/typ×miasto liczą próg na `is_active` (zgodnie z frontowym
        // resultCount), więc zajęty nośnik nadal zasila gęstość kategorii — tylko nie
        // dostaje własnego URL w sitemapie.
        $advertisements = \App\Models\Advertisement::where('is_active', true)
            ->whereIn('status', ['active', 'soon_available'])
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
            $slug = \App\Models\Advertisement::slugifyTitle($ad->title);
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
