/**
 * Prerender build-time dla botów (SEO). Po `vite build`:
 *  - bierze listę tras z sitemap.xml (kanoniczne URL-e SEO),
 *  - serwuje dist/ lokalnie (SPA fallback),
 *  - puppeteerem renderuje każdą trasę (SPA pobiera dane z prod API api.reklamap.pl),
 *  - zapisuje gotowy HTML do dist/<trasa>/index.html.
 *
 * Bot dostaje pełną treść + unikalny <title>; user dostaje to samo i JS przejmuje (mount).
 * Zero infry runtime — render leci lokalnie przy buildzie, na serwer idzie statyczny HTML.
 *
 * Użycie:
 *   node scripts/prerender.mjs                       # wszystkie trasy z sitemap
 *   ROUTE_FILTER='billboardy|/blog$' node scripts/prerender.mjs   # podzbiór (test)
 *   ROUTE_LIMIT=5 node scripts/prerender.mjs         # pierwsze 5 (test)
 */
import { createServer } from 'node:http';
import { readFile, writeFile, mkdir, stat } from 'node:fs/promises';
import { existsSync } from 'node:fs';
import { join, extname, dirname } from 'node:path';
import { fileURLToPath as toPath } from 'node:url';
import puppeteer from 'puppeteer';

const DIST = join(toPath(new URL('../dist/', import.meta.url)));
// wprost z backendu (reklamap.pl/sitemap.xml robi 301 → api; w CI redirect bywał gubiony)
const SITEMAP = process.env.SITEMAP_URL || 'https://api.reklamap.pl/sitemap.xml';
const PORT = Number(process.env.PORT || 5199);
const CONCURRENCY = Number(process.env.CONCURRENCY || 4);
const FILTER = process.env.ROUTE_FILTER ? new RegExp(process.env.ROUTE_FILTER) : null;
const LIMIT = Number(process.env.ROUTE_LIMIT || 0);
const MIN_TEXT = 150; // min. znaków treści, by uznać stronę za wyrenderowaną
const RENDER_RETRIES = Number(process.env.RENDER_RETRIES || 3); // ponowienia trasy stanowej gdy API zwróci pusto (rate-limit)
const FAIL_RATE = Number(process.env.PRERENDER_FAIL_RATE || 0.05); // > tego odsetka błędnych tras → przerwij build (nie wdrażaj dziurawego)

const MIME = { '.html':'text/html','.js':'text/javascript','.mjs':'text/javascript','.css':'text/css','.json':'application/json','.svg':'image/svg+xml','.png':'image/png','.jpg':'image/jpeg','.jpeg':'image/jpeg','.webp':'image/webp','.ico':'image/x-icon','.woff2':'font/woff2','.woff':'font/woff','.ttf':'font/ttf','.map':'application/json','.txt':'text/plain','.xml':'application/xml' };

async function startServer() {
  // Pristine base dla SPA-fallback: kopia CZYSTEGO index.html (z vite, PRZED prerenderem),
  // trzymana w pamięci. Bez tego — po sprerenderowaniu home, które nadpisuje dist/index.html
  // inline seedem — fallback serwowałby zaseedowany plik jako bazę, a seed home wyciekłby na
  // WSZYSTKIE trasy renderowane po home (szczegóły, blog…). Serwujemy z pamięci, więc nieważne,
  // że dysk już zaseedowany.
  const pristineIndex = await readFile(join(DIST, 'index.html'));

  // Szkielet SPA-fallback dla tras BEZ prerenderu (nieznane/usunięte/funkcyjne jak /porownaj,
  // /zarzadzaj): kopia CZYSTEGO index.html z wymuszonym `noindex`. .htaccess serwuje go zamiast
  // zaseedowanej home — inaczej śmieciowy/martwy URL dostaje 200 + `index` z treścią home
  // (soft-404 / duplikat home). Każda indeksowalna trasa MA własny prerenderowany plik, więc
  // „brak pliku = nie do indeksu". Pochodna czystego index.html (nie zależy od tras) — piszemy zawsze.
  {
    const src = pristineIndex.toString('utf8');
    const noindexMeta = '<meta name="robots" content="noindex, follow" />';
    const fallback = /<meta[^>]+name=["']robots["'][^>]*>/i.test(src)
      ? src.replace(/<meta[^>]+name=["']robots["'][^>]*>/i, noindexMeta)
      : src.replace('</head>', `  ${noindexMeta}\n</head>`);
    await writeFile(join(DIST, 'spa-fallback.html'), fallback);
    console.log('  ✓ spa-fallback.html zapisany (noindex) dla tras bez prerenderu');
  }

  return new Promise((resolve) => {
    const srv = createServer(async (req, res) => {
      try {
        const p = decodeURIComponent(req.url.split('?')[0]);
        const fp = join(DIST, p);
        if (p !== '/' && existsSync(fp) && (await stat(fp)).isFile()) {
          res.writeHead(200, { 'content-type': MIME[extname(fp)] || 'application/octet-stream' });
          return res.end(await readFile(fp));
        }
        res.writeHead(200, { 'content-type': 'text/html' }); // SPA fallback — pristine base
        res.end(pristineIndex);
      } catch (e) { res.writeHead(500); res.end(String(e)); }
    });
    srv.listen(PORT, () => resolve(srv));
  });
}

async function routesFromSitemap() {
  const urls = [SITEMAP, 'https://reklamap.pl/sitemap.xml'];   // api + fallback (reklamap.pl→301→api)
  const UA = 'Mozilla/5.0 (compatible; ReklaMapPrerender/1.0; +https://reklamap.pl)';
  for (let attempt = 1; attempt <= 5; attempt++) {
    for (const u of urls) {
      try {
        const xml = await (await fetch(u, { headers: { 'User-Agent': UA }, redirect: 'follow' })).text();
        let paths = [...xml.matchAll(/<loc>([^<]+)<\/loc>/g)].map((m) => m[1].replace(/https?:\/\/[^/]+/, '') || '/');
        if (paths.length > 0) {
          // Zapisz sitemapę jako statyczny plik w dist/, żeby front serwował ją sam
          // (reklamap.pl/sitemap.xml) zamiast 301 → api. Odcina discovery od awarii backendu.
          // Tylko pełny build (przy testowym FILTER/LIMIT nie nadpisujemy realnej sitemapy).
          if (!FILTER && !LIMIT) {
            try {
              await writeFile(join(DIST, 'sitemap.xml'), xml);
              console.log(`  ✓ sitemap.xml zapisany do dist/ (${[...new Set(paths)].length} URL-i, źródło: ${u})`);
            } catch (e) {
              console.log(`  ⚠ nie zapisano sitemap.xml do dist: ${e.message}`);
            }
          }
          paths = [...new Set(paths)];
          if (FILTER) paths = paths.filter((p) => FILTER.test(p));
          if (LIMIT) paths = paths.slice(0, LIMIT);
          return paths;
        }
        console.log(`  sitemap próba ${attempt} (${u}): 0 tras — ponawiam`);
      } catch (e) {
        console.log(`  sitemap próba ${attempt} (${u}) błąd: ${e.message}`);
      }
    }
    await new Promise((r) => setTimeout(r, 3000));
  }
  return [];
}

const outPath = (p) => (p === '/' ? join(DIST, 'index.html') : join(DIST, p.replace(/^\//, ''), 'index.html'));

// Trasy, które MUSZĄ mieć zaszyty stan (listingi/ogłoszenie). Pusty render tu = awaria/rate-limit
// API (sitemap zawiera tylko niepuste kategorie/miasta), więc ponawiamy i NIGDY nie zapisujemy
// szkieletu z noindex. Blog/statyczne stanu nie wymagają (kolektor zwraca null, są `index`).
const needsState = (p) =>
  p === '/' || p.startsWith('/powierzchnie-reklamowe') || p.startsWith('/powierzchnia-reklamowa');

// Zbierz stan Pinia ze strony (string albo null). Puppeteer gubi duże/reaktywne obiekty przy
// evaluate — serializujemy WEWNĄTRZ strony do stringa. null gdy brak realnych danych.
async function collectState(page) {
  return page.evaluate(() => {
    try {
      if (typeof window.__collectSSRState !== 'function') return null;
      const s = window.__collectSSRState();
      if (!s) return null;
      const hasList = s.search && Array.isArray(s.search.listings) && s.search.listings.length > 0;
      const hasAd = s.ad && s.ad.id;
      const hasBlog = s.blogPost && (s.blogPost.id || s.blogPost.slug);
      if (!hasList && !hasAd && !hasBlog) return null;
      return JSON.stringify(s);
    } catch { return null; }
  }).catch(() => null);
}

async function navigateAndCollect(page, p, needState) {
  await page.goto(`http://localhost:${PORT}${p}`, { waitUntil: 'networkidle0', timeout: 45000 });
  // Czekaj aż #app ma treść, a dla tras stanowych — aż kolektor zwróci REALNE dane
  // (listingi/ogłoszenie). Eliminuje wyścig „szkielet >150 zn., ale dane z API jeszcze nie przyszły".
  await page.waitForFunction(
    (min, needState) => {
      const a = document.querySelector('#app');
      const hasText = a && a.innerText && a.innerText.trim().length > min;
      if (!hasText) return false;
      if (!needState) return true;
      try {
        if (typeof window.__collectSSRState !== 'function') return false;
        const s = window.__collectSSRState();
        return !!(s && ((s.search && Array.isArray(s.search.listings) && s.search.listings.length > 0) || (s.ad && s.ad.id)));
      } catch { return false; }
    },
    { timeout: 20000 }, MIN_TEXT, needState,
  ).catch(() => {});
  return collectState(page);
}

function visibleLen(html) {
  return html.replace(/<script[\s\S]*?<\/script>/g, '').replace(/<style[\s\S]*?<\/style>/g, '')
    .replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().length;
}

async function render(browser, p, stats) {
  const page = await browser.newPage();
  const needState = needsState(p);
  try {
    // Zaszyj stan Pinia (listingi/ogłoszenie) w prerenderze — bez tego przy hydratacji Vue kasuje
    // prerenderowaną treść i re-fetchuje z api.reklamap.pl; w oknie renderowania Googlebota ten
    // fetch bywa ucinany → pusta strona. Seed z window.__INITIAL_STATE__ (czytany przez
    // useSearchStore) to eliminuje. Trasy stanowe PONAWIAMY, gdy kolektor nie zwrócił danych —
    // najczęstsza przyczyna to rate-limit api.reklamap.pl przy serii ~985 żądań w buildzie
    // (skutkował zamrożeniem noindex-szkieletu, np. Kłodzko 140 ofert → noindex).
    let ssrJson = null;
    for (let attempt = 1; attempt <= RENDER_RETRIES; attempt++) {
      ssrJson = await navigateAndCollect(page, p, needState);
      if (!needState || ssrJson) break;
      console.log(`  ↻ ${p} — brak danych (próba ${attempt}/${RENDER_RETRIES}); API mogło rate-limitować — ponawiam`);
      await new Promise((r) => setTimeout(r, 1500 * attempt));
    }

    let html = await page.content();
    // Usuń JAKIKOLWIEK odziedziczony seed z bazy przed (ewentualnym) wstrzyknięciem własnego —
    // nie-listowe trasy zostają czyste, listowe mają dokładnie jeden poprawny seed.
    html = html.replace(/<script>window\.__INITIAL_STATE__=[\s\S]*?<\/script>/g, '');

    // GUARD: trasa stanowa BEZ danych po ponowieniach = szkielet (zwykle noindex). NIE zapisuj —
    // brak pliku sprawia, że .htaccess odda `index`owy szkielet home, co jest bezpieczniejsze niż
    // zamrożony noindex zaindeksowany z sitemapy. Build zliczy to jako błąd (patrz FAIL_RATE).
    if (needState && !ssrJson) {
      stats.err++; stats.failed.push(p);
      console.log(`  ✗ ${p} — brak danych po ${RENDER_RETRIES} próbach; NIE zapisuję szkieletu (uniknięcie noindex)`);
      return;
    }

    let seedInfo = '';
    if (ssrJson) {
      html = html.replace('</head>', `<script>window.__INITIAL_STATE__=${ssrJson.replace(/</g, '\\u003c')}</script></head>`);
      try {
        const st = JSON.parse(ssrJson);
        if (st.search && st.search.listings) seedInfo = `, seed ${st.search.listings.length} list.`;
        else if (st.ad) seedInfo = `, seed ad#${st.ad.id}`;
      } catch { /* noop */ }
    }
    const op = outPath(p);
    await mkdir(dirname(op), { recursive: true });
    await writeFile(op, html);
    const len = visibleLen(html);
    if (len > MIN_TEXT) { stats.ok++; console.log(`  ✓ ${p}  (${len} zn.${seedInfo})`); }
    else { stats.thin++; console.log(`  ⚠ ${p}  (tylko ${len} zn. — sprawdź)`); }
  } catch (e) {
    stats.err++; stats.failed.push(p); console.log(`  ✗ ${p} — ${e.message}`);
  } finally { await page.close(); }
}

const srv = await startServer();
const routes = await routesFromSitemap();
console.log(`Tras do prerenderu: ${routes.length} (z ${SITEMAP})`);
if (routes.length === 0) {
  console.error('BŁĄD: sitemap zwróciła 0 tras — przerywam, żeby NIE wdrożyć pustego builda.');
  srv.close();
  process.exit(1);
}
const browser = await puppeteer.launch({
  headless: 'new',
  args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-web-security', '--disable-features=IsolateOrigins,site-per-process'],
});
const stats = { ok: 0, thin: 0, err: 0, failed: [] };
let i = 0;
const worker = async () => { while (i < routes.length) { await render(browser, routes[i++], stats); } };
await Promise.all(Array.from({ length: CONCURRENCY }, worker));
await browser.close();
srv.close();
console.log(`\nGotowe: ${stats.ok} OK | ${stats.thin} chude | ${stats.err} błędy`);
if (stats.failed.length > 0) {
  console.log(`Trasy nieudane (nie zapisane):\n${stats.failed.map((p) => `  - ${p}`).join('\n')}`);
}
// Przerwij build (deploy.sh nie wdroży), gdy nic się nie udało LUB odsetek błędów > FAIL_RATE —
// żeby przejściowa awaria api.reklamap.pl nie wypchnęła na prod builda z dziurami / brakiem stron.
const total = routes.length || 1;
const failRate = stats.err / total;
if (stats.ok === 0 || failRate > FAIL_RATE) {
  console.error(`BŁĄD: ${stats.err}/${total} tras nieudanych (${(failRate * 100).toFixed(1)}% > ${(FAIL_RATE * 100).toFixed(1)}%) — przerywam, żeby NIE wdrożyć dziurawego builda.`);
  process.exit(1);
}
process.exit(0);
