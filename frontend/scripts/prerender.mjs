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

const MIME = { '.html':'text/html','.js':'text/javascript','.mjs':'text/javascript','.css':'text/css','.json':'application/json','.svg':'image/svg+xml','.png':'image/png','.jpg':'image/jpeg','.jpeg':'image/jpeg','.webp':'image/webp','.ico':'image/x-icon','.woff2':'font/woff2','.woff':'font/woff','.ttf':'font/ttf','.map':'application/json','.txt':'text/plain','.xml':'application/xml' };

async function startServer() {
  // Pristine base dla SPA-fallback: kopia CZYSTEGO index.html (z vite, PRZED prerenderem),
  // trzymana w pamięci. Bez tego — po sprerenderowaniu home, które nadpisuje dist/index.html
  // inline seedem — fallback serwowałby zaseedowany plik jako bazę, a seed home wyciekłby na
  // WSZYSTKIE trasy renderowane po home (szczegóły, blog…). Serwujemy z pamięci, więc nieważne,
  // że dysk już zaseedowany.
  const pristineIndex = await readFile(join(DIST, 'index.html'));
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

function visibleLen(html) {
  return html.replace(/<script[\s\S]*?<\/script>/g, '').replace(/<style[\s\S]*?<\/style>/g, '')
    .replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().length;
}

async function render(browser, p, stats) {
  const page = await browser.newPage();
  try {
    await page.goto(`http://localhost:${PORT}${p}`, { waitUntil: 'networkidle0', timeout: 45000 });
    await page.waitForFunction(
      (min) => { const a = document.querySelector('#app'); return a && a.innerText && a.innerText.trim().length > min; },
      { timeout: 20000 }, MIN_TEXT,
    ).catch(() => {});
    // Zaszyj stan Pinia (listingi) w prerenderze — bez tego przy hydratacji Vue kasuje
    // prerenderowaną treść i re-fetchuje z api.reklamap.pl; w oknie renderowania Googlebota
    // ten fetch bywa ucinany → pusta strona („Nie znaleziono ogłoszeń"). Seed z
    // window.__INITIAL_STATE__ (czytany przez useSearchStore) to eliminuje.
    // Serializuj stan WEWNĄTRZ strony (zwracamy string, nie obiekt) — puppeteer potrafi zgubić
    // duże/reaktywne obiekty przy evaluate; string jest zawsze bezpieczny. Zaszywamy tylko gdy
    // realnie są listingi (strony-listy: home/kategorie/miasta; szczegóły ogłoszenia pomijamy).
    // Zaszywaj stan zwrócony przez kolektor: `search` (siatka na home/listach) lub `ad` (strony
    // szczegółów). Kolektor sam zwraca null tam, gdzie nic sensownego nie ma (blog/statyczne),
    // więc nie dopasowujemy tras po stringu. Serializacja WEWNĄTRZ strony (string, nie obiekt) —
    // puppeteer gubi duże/reaktywne obiekty przy evaluate.
    const ssrJson = await page.evaluate(() => {
      try {
        if (typeof window.__collectSSRState !== 'function') return null;
        const s = window.__collectSSRState();
        if (!s) return null;
        const hasList = s.search && Array.isArray(s.search.listings) && s.search.listings.length > 0;
        const hasAd = s.ad && s.ad.id;
        if (!hasList && !hasAd) return null;
        return JSON.stringify(s);
      } catch { return null; }
    }).catch(() => null);
    let html = await page.content();
    // Belt-and-suspenders: usuń JAKIKOLWIEK odziedziczony seed z bazy, zanim (ewentualnie)
    // wstrzykniemy własny — gwarantuje, że nie-listowe trasy są czyste, a listowe mają dokładnie
    // jeden, poprawny seed. (JSON ma < zescapowane do <, więc regex nie utnie się za wcześnie.)
    html = html.replace(/<script>window\.__INITIAL_STATE__=[\s\S]*?<\/script>/g, '');
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
    stats.err++; console.log(`  ✗ ${p} — ${e.message}`);
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
const stats = { ok: 0, thin: 0, err: 0 };
let i = 0;
const worker = async () => { while (i < routes.length) { await render(browser, routes[i++], stats); } };
await Promise.all(Array.from({ length: CONCURRENCY }, worker));
await browser.close();
srv.close();
console.log(`\nGotowe: ${stats.ok} OK | ${stats.thin} chude | ${stats.err} błędy`);
process.exit(stats.err > 0 && stats.ok === 0 ? 1 : 0);
